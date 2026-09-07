<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Where a signup came from, caught on the landing request. The referrer only
 * exists there: by /auth/google it is our own domain, and after the Google round
 * trip it is accounts.google.com. Getting the timing wrong records nothing
 * useful, which is why these tests are about *when* it is captured.
 */
class SignupSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_request_is_remembered(): void
    {
        $this->get('/save-to-google-drive', ['referer' => 'https://www.google.com/']);

        $this->assertSame('https://www.google.com/', session('signup_source.referrer'));
        $this->assertSame('/save-to-google-drive', session('signup_source.landing'));
    }

    public function test_a_later_page_view_does_not_overwrite_the_first(): void
    {
        $this->get('/save-to-google-drive', ['referer' => 'https://www.google.com/']);

        // The exact bug this guards: by the second hop the referrer is us.
        $this->get('/pricing', ['referer' => 'https://wetodrive.com/']);

        $this->assertSame('https://www.google.com/', session('signup_source.referrer'));
        $this->assertSame('/save-to-google-drive', session('signup_source.landing'));
    }

    public function test_the_query_string_is_kept_so_utm_survives(): void
    {
        $this->get('/?utm_source=newsletter&utm_campaign=launch', ['referer' => 'https://mail.example.com/']);

        $this->assertSame('/?utm_source=newsletter&utm_campaign=launch', session('signup_source.landing'));
    }

    public function test_a_signed_in_user_is_not_recaptured(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/', ['referer' => 'https://www.google.com/']);

        $this->assertNull(session('signup_source'));
    }

    public function test_referrers_are_read_as_channels(): void
    {
        $cases = [
            'https://www.google.com/' => 'Google search',
            'https://www.bing.com/search?q=x' => 'Bing',
            'https://chatgpt.com/' => 'ChatGPT',
            'https://www.reddit.com/r/videography' => 'Reddit',
            'https://wetodrive.com/pricing' => 'Returning visitor',
            'https://some-blog.example/post' => 'some-blog.example',
        ];

        foreach ($cases as $referrer => $expected) {
            $user = User::factory()->make(['signup_referrer' => $referrer, 'signup_landing' => '/']);
            $this->assertSame($expected, $user->signupChannel(), $referrer);
        }
    }

    public function test_a_share_claim_is_its_own_channel(): void
    {
        $user = User::factory()->make([
            'signup_landing' => '/share/abc123',
            'signup_referrer' => null,
        ]);

        $this->assertSame('Shared with them', $user->signupChannel());
    }

    public function test_users_from_before_this_shipped_read_as_unknown(): void
    {
        $user = User::factory()->make(['signup_referrer' => null, 'signup_landing' => null]);

        $this->assertSame('Direct or unknown', $user->signupChannel());
    }
}
