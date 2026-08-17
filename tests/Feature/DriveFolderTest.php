<?php

namespace Tests\Feature;

use App\Models\DriveFolder;
use App\Models\User;
use App\Services\DriveFolderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The app holds only the drive.file scope, so it can never search Drive for a
 * folder it made earlier. Whatever drive_folders forgets is unreachable, and the
 * user silently accumulates duplicates. These cover that contract.
 */
class DriveFolderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A stand-in for Google_Service_Drive->files, recording what it was asked to
     * create and pretending anything it created still exists.
     */
    private function fakeDrive(array $missing = [], string $prefix = 'folder-'): object
    {
        return new class($missing, $prefix) {
            public array $created = [];
            public array $checked = [];
            private int $n = 0;

            public function __construct(private array $missing, private string $prefix) {}

            public function get($id, $opts = [])
            {
                $this->checked[] = $id;

                if (in_array($id, $this->missing, true)) {
                    throw new \Google\Service\Exception('File not found', 404);
                }

                return new class {
                    public function getTrashed() { return false; }
                };
            }

            public function create($file, $opts = [])
            {
                $this->created[] = [
                    'name' => $file->getName(),
                    'parents' => $file->getParents(),
                    'mimeType' => $file->getMimeType(),
                ];
                $id = $this->prefix . (++$this->n);

                return new class($id) {
                    public function __construct(private string $id) {}
                    public function getId() { return $this->id; }
                };
            }
        };
    }

    /** Build the service with the fake wired in place of the real Drive client. */
    private function serviceWith(object $files): DriveFolderService
    {
        return new DriveFolderService(new class($files) {
            public function __construct(public $files) {}
        });
    }

    public function test_it_normalizes_paths(): void
    {
        $this->assertSame('', DriveFolderService::normalizePath(null));
        $this->assertSame('', DriveFolderService::normalizePath('   '));
        $this->assertSame('', DriveFolderService::normalizePath('/'));
        $this->assertSame('Acme', DriveFolderService::normalizePath('/Acme/'));
        $this->assertSame('Clients/Acme', DriveFolderService::normalizePath('Clients//Acme'));
        // Trailing spaces would otherwise make a second, identical-looking folder.
        $this->assertSame('Clients/Acme', DriveFolderService::normalizePath(' Clients / Acme '));
        $this->assertSame('Clients/Acme', DriveFolderService::normalizePath('Clients\\Acme'));
    }

    public function test_it_rejects_traversal_and_excessive_depth(): void
    {
        foreach (['../etc', 'a/../b', '.'] as $bad) {
            try {
                DriveFolderService::normalizePath($bad);
                $this->fail("accepted a traversal path: {$bad}");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('cannot be', $e->getMessage());
            }
        }

        $this->expectException(\InvalidArgumentException::class);
        DriveFolderService::normalizePath('a/b/c/d/e/f/g');
    }

    public function test_it_creates_each_missing_level_at_the_top_of_my_drive(): void
    {
        $user = User::factory()->create();
        $files = $this->fakeDrive();

        $id = $this->serviceWith($files)->resolve($user, 'Clients/Acme');

        $this->assertSame('folder-2', $id);
        // No app-owned wrapper folder: 'Clients' sits at the top of My Drive.
        $this->assertSame(['Clients', 'Acme'], array_column($files->created, 'name'));
        $this->assertSame([['root'], ['folder-1']], array_column($files->created, 'parents'));
        $this->assertSame('application/vnd.google-apps.folder', $files->created[0]['mimeType']);

        $this->assertDatabaseHas('drive_folders', ['user_id' => $user->id, 'path' => 'Clients', 'drive_folder_id' => 'folder-1']);
        $this->assertDatabaseHas('drive_folders', ['user_id' => $user->id, 'path' => 'Clients/Acme', 'drive_folder_id' => 'folder-2']);
    }

    public function test_no_folder_means_my_drive_and_touches_drive_at_all(): void
    {
        // The pre-folder behaviour, and it must stay free: no folder created, no
        // API call made, and the upload simply omits a parent.
        $user = User::factory()->create();
        $files = $this->fakeDrive();

        $this->assertNull($this->serviceWith($files)->resolve($user, null));
        $this->assertNull($this->serviceWith($files)->resolve($user, '  '));

        $this->assertSame([], $files->created);
        $this->assertSame([], $files->checked);
        $this->assertDatabaseCount('drive_folders', 0);
    }

    public function test_it_reuses_remembered_folders_instead_of_making_duplicates(): void
    {
        $user = User::factory()->create();

        $first = $this->fakeDrive();
        $this->serviceWith($first)->resolve($user, 'Acme');
        $this->assertCount(1, $first->created);

        $second = $this->fakeDrive();
        $id = $this->serviceWith($second)->resolve($user, 'Acme');

        $this->assertSame('folder-1', $id, 'a second transfer should land in the same folder');
        $this->assertSame([], $second->created, 'nothing should have been created the second time');
    }

    public function test_it_recreates_a_folder_the_user_deleted_in_drive(): void
    {
        // Without this the cached id 404s forever and every future transfer to
        // that path fails, looking like a broken app rather than a gone folder.
        $user = User::factory()->create();

        $this->serviceWith($this->fakeDrive())->resolve($user, 'Acme');

        $files = $this->fakeDrive(missing: ['folder-1'], prefix: 'remade-');
        $id = $this->serviceWith($files)->resolve($user, 'Acme');

        $this->assertSame(['Acme'], array_column($files->created, 'name'));
        $this->assertSame('remade-1', $id, 'should point at the new folder, not the deleted one');
        $this->assertDatabaseHas('drive_folders', ['user_id' => $user->id, 'path' => 'Acme', 'drive_folder_id' => $id]);
        $this->assertDatabaseCount('drive_folders', 1); // replaced, not duplicated
    }

    public function test_resolving_marks_the_folder_as_recently_used(): void
    {
        $user = User::factory()->create();
        $this->serviceWith($this->fakeDrive())->resolve($user, 'Acme');

        $this->assertNotNull(DriveFolder::firstWhere(['user_id' => $user->id, 'path' => 'Acme'])->last_used_at);
    }

    public function test_the_homepage_offers_the_users_own_folders_only(): void
    {
        $user = User::factory()->create();
        DriveFolder::create(['user_id' => $user->id, 'path' => 'Acme', 'drive_folder_id' => 'f1', 'last_used_at' => now()]);
        DriveFolder::create(['user_id' => User::factory()->create()->id, 'path' => 'SomeoneElse', 'drive_folder_id' => 'f2', 'last_used_at' => now()]);

        $this->actingAs($user)->get('/')->assertOk()
            ->assertSee('Acme')
            ->assertDontSee('SomeoneElse');
    }

    public function test_a_picked_folder_is_remembered_so_it_can_be_reached_again(): void
    {
        // Picking is what grants access to a folder this app did not create, and
        // under drive.file there is no way to search for it later. Forgetting the
        // id makes the folder permanently unreachable.
        $user = User::factory()->create();
        $files = $this->fakeDrive();

        $this->serviceWith($files)->remember($user, 'Marketing', 'picked-123');

        $this->assertDatabaseHas('drive_folders', [
            'user_id' => $user->id,
            'path' => 'Marketing',
            'drive_folder_id' => 'picked-123',
        ]);
        $this->assertSame([], $files->created, 'a picked folder already exists, nothing to create');
    }

    public function test_resolving_a_picked_folder_by_name_reuses_it_rather_than_duplicating(): void
    {
        // The recent chips submit a name, so a picked folder must be found that
        // way too, or a chip would quietly create a second folder beside it.
        $user = User::factory()->create();

        $this->serviceWith($this->fakeDrive())->remember($user, 'Marketing', 'picked-123');

        $files = $this->fakeDrive();
        $id = $this->serviceWith($files)->resolve($user, 'Marketing');

        $this->assertSame('picked-123', $id);
        $this->assertSame([], $files->created);
    }

    public function test_the_picker_token_endpoint_is_closed_to_guests(): void
    {
        $this->get('/drive/picker-token')->assertRedirect();
    }

    public function test_the_picker_token_endpoint_reports_a_reconnect_rather_than_500(): void
    {
        // A user whose Google token cannot be refreshed should get a message they
        // can act on, not a stack trace, and the response must never be cached.
        $user = User::factory()->create(['google_token' => json_encode(['access_token' => 'x', 'expires_in' => -1])]);

        $response = $this->actingAs($user)->get('/drive/picker-token');

        $this->assertContains($response->status(), [200, 401]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_the_browse_button_only_appears_when_a_picker_key_is_configured(): void
    {
        $user = User::factory()->create();

        config(['services.google.picker_key' => null]);
        $this->actingAs($user)->get('/')->assertOk()->assertDontSee('id="browseDrive"', false);

        config(['services.google.picker_key' => 'AIzaTestKey']);
        $this->actingAs($user)->get('/')->assertOk()->assertSee('id="browseDrive"', false);
    }

    public function test_it_rebuilds_a_senders_folder_structure_under_the_destination(): void
    {
        // WeTransfer flattens an uploaded folder into files named
        // "Shoot Day 1/Selects/hero-take.mov", so the structure has to be
        // rebuilt from the name, underneath wherever the user chose.
        $user = User::factory()->create();
        $files = $this->fakeDrive();

        $id = $this->serviceWith($files)->resolveWithin($user, 'dest-1', 'NLWC SPM', 'Shoot Day 1/Selects');

        $this->assertSame('folder-2', $id);
        $this->assertSame(['Shoot Day 1', 'Selects'], array_column($files->created, 'name'));
        // The first level hangs off the chosen destination, not the Drive root.
        $this->assertSame([['dest-1'], ['folder-1']], array_column($files->created, 'parents'));

        // Cache keys carry the destination so the same subfolder name under a
        // different destination cannot collide on one Drive id.
        $this->assertDatabaseHas('drive_folders', ['user_id' => $user->id, 'path' => 'NLWC SPM/Shoot Day 1/Selects']);
    }

    public function test_a_file_at_the_top_of_a_transfer_stays_in_the_destination(): void
    {
        $user = User::factory()->create();
        $files = $this->fakeDrive();

        $this->assertSame('dest-1', $this->serviceWith($files)->resolveWithin($user, 'dest-1', 'Dest', ''));
        $this->assertSame([], $files->created);
    }

    public function test_sender_folders_are_reused_across_files_in_one_transfer(): void
    {
        // Four files in the same folder must not make four folders.
        $user = User::factory()->create();
        $files = $this->fakeDrive();
        $service = $this->serviceWith($files);

        $a = $service->resolveWithin($user, 'dest-1', 'Dest', 'Shoot Day 1');
        $b = $service->resolveWithin($user, 'dest-1', 'Dest', 'Shoot Day 1');

        $this->assertSame($a, $b);
        $this->assertCount(1, $files->created);
    }

    public function test_a_bad_folder_path_is_rejected_before_anything_transfers(): void
    {
        // X-Requested-With is what $request->ajax() keys off, and what the form
        // script actually sends; without it the controller answers with a redirect.
        $this->actingAs(User::factory()->create())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/transfer', [
                'wetransfer_url' => 'https://we.tl/t-abc123',
                'destination_folder' => '../../etc',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
