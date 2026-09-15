<?php

namespace Tests\Feature;

use App\Http\Controllers\StreamProgressController;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\DropboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * Dropbox as a second destination. The upload is a session of 8MB appends, so
 * these pin the parts that decide whether a file lands whole: offsets, the
 * retry that Dropbox already took, and a download that ended early.
 */
class DropboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.dropbox.client_id' => 'key',
            'services.dropbox.client_secret' => 'secret',
            'services.dropbox.redirect' => 'https://wetodrive.test/auth/dropbox/callback',
        ]);

        Sleep::fake();
    }

    private function connectedUser(): User
    {
        return User::factory()->create(['dropbox_refresh_token' => 'refresh-token']);
    }

    /** @return resource */
    private function streamOf(int $bytes)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, str_repeat('x', $bytes));
        rewind($stream);

        return $stream;
    }

    private function arg(Request $request): array
    {
        return json_decode($request->header('Dropbox-API-Arg')[0], true);
    }

    public function test_a_file_is_sent_in_chunks_and_committed_to_its_path(): void
    {
        $size = DropboxService::CHUNK_SIZE + 10;

        Http::fake([
            'api.dropboxapi.com/oauth2/token' => Http::response(['access_token' => 'at']),
            '*/upload_session/start' => Http::response(['session_id' => 's1']),
            '*/upload_session/append_v2' => Http::response('null'),
            '*/upload_session/finish' => Http::response(['path_display' => '/Clients/Acme/take one.mov']),
        ]);

        $meta = DropboxService::for($this->connectedUser())
            ->upload($this->streamOf($size), DropboxService::path('/Clients/Acme', '', 'take one.mov'), null, $size);

        $this->assertSame('/Clients/Acme/take one.mov', $meta['path_display']);

        $offsets = collect(Http::recorded())
            ->filter(fn ($pair) => str_ends_with($pair[0]->url(), 'append_v2'))
            ->map(fn ($pair) => $this->arg($pair[0])['cursor']['offset'])
            ->values()->all();
        $this->assertSame([0, DropboxService::CHUNK_SIZE], $offsets);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'finish')
            && $this->arg($r)['cursor']['offset'] === $size
            && $this->arg($r)['commit']['path'] === '/Clients/Acme/take one.mov');
    }

    public function test_a_retried_chunk_that_dropbox_already_took_is_not_an_error(): void
    {
        Http::fake([
            'api.dropboxapi.com/oauth2/token' => Http::response(['access_token' => 'at']),
            '*/upload_session/start' => Http::response(['session_id' => 's1']),
            '*/upload_session/append_v2' => Http::sequence()
                ->push('upstream timeout', 503)
                ->push(['error_summary' => 'incorrect_offset/', 'error' => ['.tag' => 'incorrect_offset', 'correct_offset' => 100]], 409),
            '*/upload_session/finish' => Http::response(['path_display' => '/a.bin']),
        ]);

        $meta = DropboxService::for($this->connectedUser())->upload($this->streamOf(100), '/a.bin', null, 100);

        $this->assertSame('/a.bin', $meta['path_display']);
    }

    public function test_a_download_that_ended_early_is_never_committed(): void
    {
        Http::fake([
            'api.dropboxapi.com/oauth2/token' => Http::response(['access_token' => 'at']),
            '*/upload_session/start' => Http::response(['session_id' => 's1']),
            '*/upload_session/append_v2' => Http::response('null'),
            '*' => Http::response(['path_display' => '/a.bin']),
        ]);

        try {
            DropboxService::for($this->connectedUser())->upload($this->streamOf(50), '/a.bin', null, 100);
            $this->fail('a short stream should not be committed');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('ended early', $e->getMessage());
        }

        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), 'finish'));
    }

    public function test_paths_and_links_are_built_from_any_mix_of_parts(): void
    {
        $this->assertSame('/x.mov', DropboxService::path(null, '', 'x.mov'));
        $this->assertSame('/Clients/Acme/Day 1/x.mov', DropboxService::path('/Clients/Acme', 'Day 1/', 'x.mov'));
        $this->assertSame('https://www.dropbox.com/home/Clients/Day%201', DropboxService::webUrl('/Clients/Day 1'));
        $this->assertSame('https://www.dropbox.com/home', DropboxService::webUrl(null));
    }

    public function test_connecting_stores_the_refresh_token(): void
    {
        Http::fake(['api.dropboxapi.com/oauth2/token' => Http::response([
            'access_token' => 'at', 'refresh_token' => 'rt', 'account_id' => 'dbid:1',
        ])]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['dropbox_state' => 'expected'])
            ->get('/auth/dropbox/callback?state=expected&code=c')
            ->assertRedirect(route('dropbox'));

        $this->assertSame('rt', $user->fresh()->dropbox_refresh_token);
        $this->assertTrue($user->fresh()->hasDropbox());
    }

    public function test_a_callback_this_browser_did_not_start_is_refused(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['dropbox_state' => 'expected'])
            ->get('/auth/dropbox/callback?state=forged&code=c')
            ->assertRedirect(route('dropbox'));

        Http::assertNothingSent();
        $this->assertFalse($user->fresh()->hasDropbox());
    }

    public function test_a_dropbox_transfer_is_refused_before_download_when_not_connected(): void
    {
        $this->actingAs(User::factory()->create())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/transfer', ['wetransfer_url' => 'https://we.tl/t-ABC123', 'destination' => 'dropbox'])
            ->assertStatus(403)
            ->assertJson(['success' => false, 'needs_dropbox' => true]);
    }

    public function test_the_dropbox_page_does_not_exist_until_dropbox_is_configured(): void
    {
        config(['services.dropbox.client_id' => null]);

        $this->actingAs($this->connectedUser())->get('/dropbox')->assertNotFound();
    }

    public function test_a_connected_user_gets_a_form_that_sends_to_dropbox(): void
    {
        $r = $this->actingAs($this->connectedUser())->get('/dropbox')->assertOk();

        $r->assertSee('name="destination" value="dropbox"', false);

        // Element ids are the contract with partials/transfer-script.
        foreach ([
            'transferForm', 'transferFormContainer', 'transferButton', 'progressContainer',
            'progressBar', 'progressPercent', 'progressStatus', 'progressFilename',
            'bytesTransferred', 'totalSize', 'statusMessage', 'completionMessage', 'wetransfer_url',
        ] as $id) {
            $r->assertSee('id="' . $id . '"', false);
        }
    }

    public function test_someone_without_dropbox_is_asked_to_connect_it_first(): void
    {
        $this->actingAs(User::factory()->create())->get('/dropbox')
            ->assertOk()
            ->assertSee(route('auth.dropbox'), false)
            ->assertDontSee('id="transferForm"', false);
    }

    public function test_a_guest_signs_in_and_comes_back_to_the_dropbox_page(): void
    {
        $this->get('/dropbox')->assertOk()->assertSee(route('auth.google'), false);

        $this->assertSame(route('dropbox'), session('url.intended'));
    }

    public function test_each_page_only_picks_up_its_own_transfer(): void
    {
        StreamProgressController::markActiveTransfer(7, 'transfer_dbx', 'dropbox');
        StreamProgressController::updateProgress('transfer_dbx', 0, 100);

        $this->assertSame('transfer_dbx', StreamProgressController::activeTransferFor(7, 'dropbox'));
        $this->assertNull(StreamProgressController::activeTransferFor(7, 'drive'));

        // Still one transfer at a time, whichever page started it.
        $this->assertSame('transfer_dbx', StreamProgressController::runningTransferFor(7));
    }

    public function test_the_page_is_built_to_rank_for_wetransfer_to_dropbox(): void
    {
        $html = $this->get('/dropbox')->assertOk()->getContent();

        $this->assertStringContainsString('<title>WeTransfer to Dropbox', $html);
        $this->assertMatchesRegularExpression('/<h1>[^<]*WeTransfer to Dropbox/', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . route('dropbox') . '">', $html);

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
        $schema = json_decode($m[1] ?? '', true);
        $this->assertSame('FAQPage', $schema[1]['@type'] ?? null, 'FAQ structured data should parse');

        // Every structured question is one a visitor can actually read on the page.
        foreach ($schema[1]['mainEntity'] as $qa) {
            $this->assertStringContainsString('<summary>' . e($qa['name']) . '</summary>', $html);
        }
    }

    public function test_the_footer_links_the_page_only_where_it_exists(): void
    {
        $this->get('/')->assertOk()->assertSee('>WeTransfer to Dropbox</a>', false);

        config(['services.dropbox.client_id' => null]);
        $this->get('/')->assertOk()->assertDontSee('>WeTransfer to Dropbox</a>', false);
    }

    private function fakeDropboxSignIn(array $account): void
    {
        Http::fake([
            'api.dropboxapi.com/oauth2/token' => Http::response([
                'access_token' => 'at', 'refresh_token' => 'rt', 'account_id' => $account['account_id'],
            ]),
            'api.dropboxapi.com/2/users/get_current_account' => Http::response($account + [
                'name' => ['display_name' => 'Dropbox Person'],
            ]),
        ]);
    }

    private function dropboxCallback()
    {
        return $this->withSession(['dropbox_state' => 'expected'])
            ->get('/auth/dropbox/callback?state=expected&code=c');
    }

    public function test_a_new_visitor_signs_up_with_dropbox_alone(): void
    {
        Mail::fake();
        $this->fakeDropboxSignIn(['account_id' => 'dbid:new', 'email' => 'new@example.com', 'email_verified' => true]);

        $this->dropboxCallback()->assertRedirect(route('dropbox'));

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Dropbox Person', $user->name);
        $this->assertTrue($user->hasDropbox());
        $this->assertFalse($user->hasDriveAccess(), 'a Dropbox-only account has no Drive to send to');
        Mail::assertSent(WelcomeMail::class);
    }

    public function test_a_verified_dropbox_email_signs_in_to_the_existing_google_account(): void
    {
        Mail::fake();
        $existing = User::factory()->create(['email' => 'mike@example.com', 'google_id' => 'g-1']);
        $this->fakeDropboxSignIn(['account_id' => 'dbid:mike', 'email' => 'Mike@Example.com', 'email_verified' => true]);

        $this->dropboxCallback()->assertRedirect(route('dropbox'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
        $this->assertTrue($existing->fresh()->hasDropbox());
        $this->assertTrue($existing->fresh()->hasDriveAccess(), 'adding Dropbox must not cost them Drive');
        Mail::assertNothingSent();
    }

    public function test_an_unverified_dropbox_email_signs_in_to_nothing(): void
    {
        User::factory()->create(['email' => 'mike@example.com']);
        $this->fakeDropboxSignIn(['account_id' => 'dbid:someone', 'email' => 'mike@example.com', 'email_verified' => false]);

        $this->dropboxCallback()->assertRedirect(route('dropbox'))->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(1, User::count());
        $this->assertNull(User::first()->dropbox_account_id);
    }

    public function test_a_returning_dropbox_user_is_found_by_their_dropbox_not_their_email(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com', 'dropbox_account_id' => 'dbid:same', 'dropbox_refresh_token' => 'old',
        ]);
        $this->fakeDropboxSignIn(['account_id' => 'dbid:same', 'email' => 'changed@example.com', 'email_verified' => false]);

        $this->dropboxCallback()->assertRedirect(route('dropbox'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('rt', $user->fresh()->dropbox_refresh_token);
    }

    public function test_a_dropbox_can_only_be_linked_to_one_account(): void
    {
        $previous = User::factory()->create(['dropbox_account_id' => 'dbid:shared', 'dropbox_refresh_token' => 'x']);
        $current = User::factory()->create();
        $this->fakeDropboxSignIn(['account_id' => 'dbid:shared', 'email' => 'x@example.com', 'email_verified' => true]);

        $this->actingAs($current)->dropboxCallback()->assertRedirect(route('dropbox'));

        $this->assertTrue($current->fresh()->hasDropbox());
        $this->assertFalse($previous->fresh()->hasDropbox());
    }

    /**
     * Signing out of WetoDrive leaves the browser signed in to Dropbox, which
     * then hands back the same account every time. Found by hand on 15 Sep
     * trying to reach a second Dropbox.
     */
    public function test_choosing_a_different_dropbox_account_makes_dropbox_ask_again(): void
    {
        $this->assertStringNotContainsString('force_reauthentication', DropboxService::authorizeUrl('s'));
        $this->assertStringContainsString('force_reauthentication=true', DropboxService::authorizeUrl('s', chooseAccount: true));

        $this->get('/auth/dropbox')->assertRedirect()->assertRedirectContains('dropbox.com/oauth2/authorize');
        $this->assertStringNotContainsString('force_reauthentication', $this->get('/auth/dropbox')->headers->get('Location'));
        $this->get('/auth/dropbox?switch=1')->assertRedirectContains('force_reauthentication=true');

        $this->get('/dropbox')->assertOk()->assertSee(route('auth.dropbox', ['switch' => 1]), false);
    }

    public function test_the_page_offers_dropbox_sign_in_before_google(): void
    {
        $html = $this->get('/dropbox')->assertOk()->getContent();

        $this->assertStringContainsString(route('auth.dropbox'), $html);
        $this->assertLessThan(
            strpos($html, route('auth.google') . '"', strpos($html, '<h1>')),
            strpos($html, route('auth.dropbox'), strpos($html, '<h1>')),
            'Continue with Dropbox should come before the Google option',
        );
    }

    public function test_the_homepage_links_through_to_the_dropbox_page(): void
    {
        $link = 'href="' . route('dropbox') . '"';

        $guest = $this->get('/')->assertOk()->getContent();
        $this->assertGreaterThanOrEqual(4, substr_count($guest, $link), 'nav, mobile menu, hero and footer');
        $this->assertStringContainsString('Save WeTransfer to Dropbox</a>', $guest);

        $this->actingAs(User::factory()->create())->get('/')->assertOk()
            ->assertSee('Send WeTransfer files to Dropbox</a>', false);

        config(['services.dropbox.client_id' => null]);
        $this->get('/')->assertOk()->assertDontSee($link, false);
    }

    public function test_a_dropbox_only_account_signs_out_rather_than_disconnecting_a_drive_it_never_had(): void
    {
        $dropboxOnly = User::factory()->create(['dropbox_account_id' => 'dbid:1', 'dropbox_refresh_token' => 'rt']);

        $this->actingAs($dropboxOnly)->get('/')->assertOk()
            ->assertSee('>Sign out</button>', false)
            ->assertDontSee('Disconnect Google Drive');

        $this->post('/auth/disconnect')->assertRedirect(route('dropbox'));
        $this->assertGuest();

        // A Google account, with or without Dropbox added, keeps the old wording.
        $google = User::factory()->create(['google_id' => 'g-1', 'dropbox_account_id' => 'dbid:2']);
        $this->actingAs($google)->get('/')->assertSee('Disconnect Google Drive');
    }

    public function test_the_homepage_stays_about_google_drive(): void
    {
        // A link, not the string: the shared click tracker names /auth/dropbox in its selector.
        $this->actingAs($this->connectedUser())->get('/')->assertOk()
            ->assertDontSee('href="' . route('auth.dropbox') . '"', false)
            ->assertDontSee('name="destination"', false);
    }
}
