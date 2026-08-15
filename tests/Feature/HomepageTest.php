<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The homepage carries roughly 73% of the site's search traffic (GSC: 300 of 409
 * clicks over three months), ranking #1 for "wetodrive" and #3.2 for "wetransfer
 * to google drive". These tests exist so a redesign cannot quietly cost that.
 *
 * The pre-redesign page is kept verbatim at resources/views/home-legacy.blade.php
 * and is the baseline several of these compare against.
 */
class HomepageTest extends TestCase
{
    use RefreshDatabase;

    /** Every SEO landing page the homepage links to, with its exact anchor text. */
    private const SEO_LINKS = [
        '/wetransfer-pricing'     => 'WeTransfer Pricing',
        '/wetransfer-send-files'  => 'How to Send Files',
        '/wetransfer-upload'      => 'Upload Tutorial',
        '/wetransfer-free'        => 'Free Plan Guide',
        '/wetransfer-alternative' => 'WeTransfer Alternative',
        '/save-to-google-drive'   => 'Save to Google Drive',
    ];

    /** Keyword copy carried over from the pre-redesign homepage. */
    private const KEYWORD_COPY = [
        'Transfer files from WeTransfer to Google Drive instantly',
        'No more manual downloading and uploading',
        'Files stream directly to your Google Drive without taking up space on your device',
        'Instant Transfer',
        'Save Storage',
        'Fast &amp; Secure',
    ];

    /** The old homepage, rendered for comparison. */
    private function legacyHtml(): string
    {
        View::share('errors', new \Illuminate\Support\ViewErrorBag);
        return view('home-legacy')->render();
    }

    private function visibleText(string $html): string
    {
        $stripped = preg_replace('#<script.*?</script>|<style.*?</style>#s', ' ', $html);
        return strtolower(strip_tags($stripped));
    }

    public function test_the_homepage_is_indexable(): void
    {
        // The single most damaging possible regression: shipping the staging
        // noindex would remove the site's highest-traffic page from Google.
        $this->get('/')->assertOk()->assertDontSee('content="noindex"', false);
    }

    public function test_it_keeps_the_title_it_ranks_on(): void
    {
        $this->get('/')->assertSee('<title>WetoDrive - WeTransfer to Google Drive</title>', false);
    }

    public function test_the_h1_carries_the_brand_and_the_primary_keyword(): void
    {
        preg_match('#<h1[^>]*>(.*?)</h1>#s', $this->get('/')->getContent(), $m);

        $this->assertNotEmpty($m, 'no h1 on the page');
        $h1 = strtolower(strip_tags($m[1]));

        $this->assertStringContainsString('wetodrive', $h1, 'h1 lost the brand term');
        $this->assertStringContainsString('wetransfer', $h1, 'h1 lost the wetransfer term');
        $this->assertStringContainsString('google drive', $h1, 'h1 lost the google drive term');
    }

    public function test_it_mentions_the_brand_at_least_as_often_as_the_old_page(): void
    {
        $count = fn ($html) => substr_count($this->visibleText($html), 'wetodrive');

        $this->assertGreaterThanOrEqual(
            $count($this->legacyHtml()),
            $count($this->get('/')->getContent()),
            'the homepage mentions the brand less often than the page it replaced'
        );
    }

    public function test_it_links_every_seo_landing_page_with_the_same_anchor_text(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (self::SEO_LINKS as $path => $anchor) {
            $this->assertStringContainsString($path, $html, "lost the internal link to {$path}");
            $this->assertStringContainsString($anchor, $html, "lost the anchor text '{$anchor}'");
        }
    }

    public function test_it_carries_the_old_pages_keyword_copy(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (self::KEYWORD_COPY as $phrase) {
            $this->assertStringContainsString($phrase, $html, "lost keyword copy: {$phrase}");
        }
    }

    public function test_it_has_the_meta_the_old_page_lacked(): void
    {
        $this->get('/')
            ->assertSee('name="description"', false)
            ->assertSee('name="keywords"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_the_structured_data_is_valid_json(): void
    {
        // Blade eats a bare @context/@type, so this guards the @@ escaping.
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $this->get('/')->getContent(), $m);

        $this->assertNotEmpty($m, 'no JSON-LD block found');
        $data = json_decode(trim($m[1]), true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD did not parse');
        $this->assertSame('SoftwareApplication', $data['@type']);
    }

    public function test_the_transfer_form_is_wired_to_the_shared_script(): void
    {
        $r = $this->actingAs(User::factory()->create())->get('/')->assertOk();

        // Element ids are the contract with partials/transfer-script.
        foreach ([
            'transferForm', 'transferFormContainer', 'progressContainer', 'progressBar',
            'progressPercent', 'progressStatus', 'progressFilename', 'bytesTransferred',
            'totalSize', 'statusMessage', 'completionMessage', 'wetransfer_url',
            'data-total-transfers', 'data-transfers-remaining',
        ] as $id) {
            $r->assertSee($id, false);
        }
    }

    public function test_a_signed_in_user_can_still_reach_their_account_controls(): void
    {
        $r = $this->actingAs(User::factory()->create(['role' => 'user']))->get('/')->assertOk();

        $r->assertSee('auth/disconnect', false);
        $r->assertSee('subscription/manage', false);
        $r->assertSee('pricing', false);
    }

    public function test_an_admin_sees_the_admin_link(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/')->assertOk()->assertSee('admin/dashboard', false);
    }

    public function test_a_non_admin_does_not_see_the_admin_link(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/')->assertOk()->assertDontSee('admin/dashboard', false);
    }

    public function test_stats_are_hidden_rather_than_showing_zero(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Transfers delivered to Drive', false);
    }

    public function test_the_old_staging_url_redirects_instead_of_competing(): void
    {
        // Two live URLs with the same content would split rankings.
        $this->get('/v2')->assertRedirect('/');
    }

    public function test_the_legacy_page_still_renders_so_a_rollback_is_safe(): void
    {
        // The rollback plan is to point index() at this view. That is only true
        // for as long as it actually renders.
        $this->assertStringContainsString('WetoDrive', $this->legacyHtml());
    }
}
