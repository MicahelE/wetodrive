<?php

namespace Tests\Feature;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * A transfer keeps running after the browser closes, so the homepage has to be
 * able to find its way back to the progress stream. These cover the handoff:
 * the pointer the server leaves behind, who is allowed to read the stream, and
 * the history the finished transfer ends up in.
 */
class TransferResumeTest extends TestCase
{
    use RefreshDatabase;

    /** Put a user in the state they'd be in with a transfer already running. */
    private function startTransferFor(User $user, string $transferId = 'transfer_x'): string
    {
        Cache::put("active_transfer_{$user->id}", $transferId, 900);
        Cache::put("transfer_progress_{$transferId}", [
            'bytesTransferred' => 500,
            'totalBytes' => 1000,
            'percentage' => 50,
            'status' => 'transferring',
            'filename' => 'showreel.mp4',
        ], 900);

        return $transferId;
    }

    public function test_the_homepage_hands_back_a_running_transfer(): void
    {
        $user = User::factory()->create();
        $this->startTransferFor($user);

        $this->actingAs($user)->get('/')->assertOk()->assertSee('data-resume="transfer_x"', false);
    }

    public function test_a_pointer_to_expired_progress_is_not_offered(): void
    {
        // The pointer outliving the progress it points at would otherwise leave
        // the page stuck on a progress bar that never moves.
        $user = User::factory()->create();
        Cache::put("active_transfer_{$user->id}", 'transfer_gone', 900);

        $this->actingAs($user)->get('/')->assertOk()->assertDontSee('data-resume', false);
    }

    public function test_a_user_with_no_transfer_sees_the_plain_form(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertDontSee('data-resume', false);
    }

    public function test_a_guest_homepage_still_renders(): void
    {
        // index() reads the pointer off the authenticated user, so a guest must
        // not trip over it. This page carries most of the site's search traffic.
        $this->get('/')->assertOk()->assertDontSee('data-resume', false);
    }

    public function test_the_owner_may_read_the_progress_stream(): void
    {
        $user = User::factory()->create();
        $this->startTransferFor($user);

        $this->actingAs($user)->get('/transfer/progress?transfer_id=transfer_x')->assertOk();
    }

    public function test_another_user_may_not_read_the_progress_stream(): void
    {
        $owner = User::factory()->create();
        $this->startTransferFor($owner);

        $this->actingAs(User::factory()->create())
            ->get('/transfer/progress?transfer_id=transfer_x')
            ->assertForbidden();
    }

    public function test_a_guest_may_not_read_the_progress_stream(): void
    {
        $this->startTransferFor(User::factory()->create());

        $this->get('/transfer/progress?transfer_id=transfer_x')->assertRedirect();
    }

    public function test_the_dashboard_lists_the_users_own_transfers_only(): void
    {
        $user = User::factory()->create();
        Transfer::create([
            'user_id' => $user->id,
            'filename' => 'mine.mp4',
            'file_size' => 2048,
            'google_drive_id' => 'drive-abc',
            'transferred_at' => now(),
        ]);
        Transfer::create([
            'user_id' => User::factory()->create()->id,
            'filename' => 'theirs.mp4',
            'file_size' => 4096,
            'google_drive_id' => 'drive-xyz',
            'transferred_at' => now(),
        ]);

        $this->actingAs($user)->get('/subscription/manage')->assertOk()
            ->assertSee('mine.mp4')
            ->assertSee('https://drive.google.com/file/d/drive-abc/view', false)
            ->assertDontSee('theirs.mp4')
            ->assertDontSee('drive-xyz');
    }

    public function test_a_transfer_predating_the_new_columns_still_renders(): void
    {
        // Every row already in production looks like this.
        $user = User::factory()->create(['role' => 'admin']);
        Transfer::create([
            'user_id' => $user->id,
            'file_size' => 1024,
            'transferred_at' => now(),
        ]);

        $this->actingAs($user)->get('/subscription/manage')->assertOk();
        $this->actingAs($user)->get("/admin/users/{$user->id}")->assertOk();
    }
}
