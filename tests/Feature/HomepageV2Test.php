<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class HomepageV2Test extends TestCase {
    use RefreshDatabase;
    public function test_guest(): void {
        $r = $this->get('/v2');
        $r->assertOk()->assertSee('WetoDrive', false)->assertSee('Jawad T.', false)
          ->assertSee('Continue with Google', false);
    }
    public function test_auth_has_working_transfer_form(): void {
        $u = User::factory()->create();
        $r = $this->actingAs($u)->get('/v2');
        $r->assertOk();
        foreach (['transferForm','transferFormContainer','progressContainer','progressBar','progressPercent',
                  'bytesTransferred','totalSize','statusMessage','completionMessage','wetransfer_url',
                  'data-total-transfers','data-transfers-remaining'] as $id) {
            $r->assertSee($id, false);
        }
    }
    public function test_classic_home_still_works(): void {
        $this->get('/')->assertOk();
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('transferForm', false);
    }
}
