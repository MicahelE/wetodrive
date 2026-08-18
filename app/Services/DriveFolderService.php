<?php

namespace App\Services;

use App\Models\DriveFolder;
use App\Models\User;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Support\Facades\Log;

/**
 * Resolves a user-typed folder path to a Google Drive folder id, creating what
 * does not exist yet.
 *
 * Nothing is wrapped in an app-owned folder: with no path, files go straight to
 * My Drive exactly as they always did, and a named path is created at the top
 * level. It is the user's Drive, and they can reorganise it however they like.
 *
 * The app holds only the drive.file scope, so it can never browse the user's
 * Drive or search for a folder it created on a previous transfer. Everything it
 * can reach, it made and recorded itself. That is why every level is cached in
 * drive_folders: what this service forgets is genuinely unreachable afterwards,
 * and the user would silently accumulate duplicate folders.
 */
class DriveFolderService
{
    /** What Drive calls My Drive when used as a parent. */
    private const DRIVE_ROOT = 'root';

    private const FOLDER_MIME = 'application/vnd.google-apps.folder';

    /** Deep paths are user error, not a use case. */
    private const MAX_DEPTH = 5;

    /**
     * Deliberately untyped: tests substitute a lightweight double here, and the
     * alternative is mocking Google's client stack to exercise four lines of
     * folder walking.
     *
     * @var Google_Service_Drive
     */
    private $drive;

    public function __construct($drive)
    {
        $this->drive = $drive;
    }

    public static function for(User $user): self
    {
        return new self(new Google_Service_Drive((new StreamTransferService())->getGoogleClient($user)));
    }

    /**
     * Clean a user-supplied path into '' (the root) or 'A/B/C'.
     *
     * Static and side-effect free so the controller can validate the input while
     * it still holds the request, rather than discovering a bad path after the
     * response has been sent and the worker has detached.
     *
     * @throws \InvalidArgumentException
     */
    public static function normalizePath(?string $path): string
    {
        $segments = [];

        foreach (explode('/', str_replace('\\', '/', (string) $path)) as $segment) {
            // Drive treats leading/trailing spaces as significant, which turns a
            // stray keystroke into a second folder that looks identical.
            $segment = trim($segment);

            if ($segment === '') {
                continue; // collapses '//', and leading or trailing slashes
            }

            if ($segment === '.' || $segment === '..') {
                throw new \InvalidArgumentException('Folder names cannot be "." or "..".');
            }

            $segments[] = $segment;
        }

        if (count($segments) > self::MAX_DEPTH) {
            throw new \InvalidArgumentException('Folder path is nested too deeply (max ' . self::MAX_DEPTH . ' levels).');
        }

        return implode('/', $segments);
    }

    /**
     * The Drive folder id for this path, creating any missing level.
     *
     * Returns null when no path was given, which means My Drive itself: callers
     * omit the parents key entirely rather than naming a folder, so a user who
     * asks for nothing gets the behaviour they had before folders existed.
     *
     * Walks from the top down so each level can be given its parent. Every
     * cached id is verified on the way: a folder the user deleted in Drive would
     * otherwise fail every future transfer to that path, and the failure would
     * look like a broken app rather than a missing folder. The verification is a
     * metadata call per level against a transfer measured in gigabytes.
     */
    public function resolve(User $user, ?string $path): ?string
    {
        $path = self::normalizePath($path);

        if ($path === '') {
            return null; // straight into My Drive
        }

        $folderId = $this->walk($user, explode('/', $path), self::DRIVE_ROOT, '');

        DriveFolder::where('user_id', $user->id)->where('path', $path)
            ->update(['last_used_at' => now()]);

        return $folderId;
    }

    /**
     * Resolve a path relative to a folder that has already been chosen.
     *
     * WeTransfer flattens uploaded folders into files whose name carries the
     * whole relative path ("Shoot Day 1/Selects/hero-take.mov"), so recreating
     * that structure means walking those segments underneath the user's chosen
     * destination rather than from the root of their Drive.
     *
     * Cache keys are prefixed with the destination so the same subfolder name
     * under two different destinations does not collide on one Drive id.
     */
    public function resolveWithin(User $user, ?string $parentId, string $parentLabel, ?string $relativePath): ?string
    {
        $relativePath = self::normalizePath($relativePath);

        if ($relativePath === '') {
            return $parentId; // the file sits directly in the destination
        }

        return $this->walk(
            $user,
            explode('/', $relativePath),
            $parentId ?? self::DRIVE_ROOT,
            self::normalizePath($parentLabel),
        );
    }

    /** Create or reuse each level in turn, returning the deepest folder's id. */
    private function walk(User $user, array $segments, string $startParent, string $cachePrefix): string
    {
        $parentId = $startParent;
        $walked = $cachePrefix;

        foreach ($segments as $segment) {
            $walked = $walked === '' ? $segment : "{$walked}/{$segment}";
            $parentId = $this->resolveLevel($user, $walked, $segment, $parentId);
        }

        return $parentId;
    }

    /**
     * Record a folder the user chose through the Google Picker.
     *
     * Picking is what grants this app access to a folder it did not create, and
     * that grant is only useful for as long as we remember the id: with the
     * drive.file scope there is no way to search for it again. Stored under the
     * folder's own name so it appears in the recent list like any other.
     */
    public function remember(User $user, string $name, string $driveFolderId): void
    {
        DriveFolder::updateOrCreate(
            ['user_id' => $user->id, 'path' => self::normalizePath($name) ?: $name],
            ['drive_folder_id' => $driveFolderId, 'last_used_at' => now()],
        );
    }

    /** One level of the walk: reuse the remembered folder, or make a new one. */
    private function resolveLevel(User $user, string $path, string $name, string $parentId): string
    {
        $row = DriveFolder::firstWhere(['user_id' => $user->id, 'path' => $path]);

        if ($row && $this->stillExists($row->drive_folder_id)) {
            return $row->drive_folder_id;
        }

        if ($row) {
            Log::info('Drive folder vanished, recreating', [
                'user_id' => $user->id,
                'path' => $path,
                'old_folder_id' => $row->drive_folder_id,
            ]);
            $row->delete();
        }

        $folderId = $this->createFolder($name, $parentId);

        DriveFolder::updateOrCreate(
            ['user_id' => $user->id, 'path' => $path],
            ['drive_folder_id' => $folderId, 'last_used_at' => now()],
        );

        return $folderId;
    }

    /**
     * Can this user actually put files into that folder?
     *
     * The Picker lists everything under "Shared with me", including folders the
     * user can only read, and picking one is allowed. Uploading into it then
     * fails with insufficientParentPermissions on every single file. One user
     * lost a 311 file, 2.9GB transfer that way before this check existed, so the
     * destination is confirmed writable before anything is downloaded.
     */
    public function canAddFilesTo(string $folderId): bool
    {
        try {
            $folder = $this->drive->files->get($folderId, ['fields' => 'capabilities(canAddChildren)']);

            return (bool) $folder->getCapabilities()->getCanAddChildren();
        } catch (\Throwable $e) {
            Log::warning('Could not read folder capabilities', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** False when the folder is gone or in the bin, which are the same to us. */
    private function stillExists(string $folderId): bool
    {
        try {
            return $this->drive->files->get($folderId, ['fields' => 'id, trashed'])->getTrashed() !== true;
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 404) {
                return false;
            }
            throw $e;
        }
    }

    private function createFolder(string $name, string $parentId): string
    {
        $folder = $this->drive->files->create(
            new Google_Service_Drive_DriveFile([
                'name' => $name,
                'mimeType' => self::FOLDER_MIME,
                'parents' => [$parentId],
            ]),
            ['fields' => 'id'],
        );

        Log::info('Created Drive folder', ['name' => $name, 'id' => $folder->getId()]);

        return $folder->getId();
    }
}
