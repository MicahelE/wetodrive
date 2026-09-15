<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\DropboxService;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'batch_id',
        'filename',
        'file_size',
        'google_drive_id',
        'destination',
        'transferred_at',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    /**
     * Where to open what landed. For Dropbox, google_drive_id holds the file's
     * path, and dropbox.com has no page for one file, so it opens the folder.
     */
    public function viewUrl(): ?string
    {
        if (blank($this->google_drive_id)) {
            return null;
        }

        return $this->destination === 'dropbox'
            ? DropboxService::webUrl(dirname($this->google_drive_id))
            : "https://drive.google.com/file/d/{$this->google_drive_id}/view";
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        return self::formatSize($this->file_size);
    }

    /**
     * Static so a batch total can be formatted the same way as a single file.
     * The admin history groups a multi-file transfer into one row, and that
     * row's size is a SUM rather than any one model's file_size.
     */
    public static function formatSize(?int $bytes): string
    {
        $bytes = (int) $bytes;

        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
