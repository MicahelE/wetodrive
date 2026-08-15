<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /v2 is intended to replace the homepage, so it has to carry the live page's
 * ranking signals rather than merely look nicer. These tests fail if a redesign
 * quietly drops a keyword, an internal link, or the title.
 */
class HomepageV2Test extends TestCase
{
    use RefreshDatabase;

    /** Every SEO landing page the live homepage links to, with its exact anchor text. */
    private const SEO_LINKS = [
        '/wetransfer-pricing'    => 'WeTransfer Pricing',
        '/wetransfer-send-files' => 'How to Send Files',
        '/wetransfer-upload'     => 'Upload Tutorial',
        '/wetransfer-free'       => 'Free Plan Guide',
        '/wetransfer-alternative'=> 'WeTransfer Alternative',
        '/save-to-google-drive'  => 'Save to Google Drive',
    ];

    /** Keyword copy carried over verbatim from the live homepage. */
    private const KEYWORD_COPY = [
        'Transfer files from WeTransfer to Google Drive instantly',
        'No more manual downloading and uploading',
        'Files stream directly to your Google Drive without taking up space on your device',
        'Instant Transfer',
        'Save Storage',
        'Fast &amp; Secure',
    ];

    public function test_it_keeps_the_exact_title_the_live_homepage_ranks_on(): void
    {
        // Not "roughly the same" — byte for byte.
        $this->get('/v2')->assertSee('<title>WetoDrive - WeTransfer to Google Drive</title>', false);
    }

    /**
     * GSC: this page is #1 for "wetodrive" (93 clicks, 77% CTR) and #3.2 for
     * "wetransfer to google drive" (40 clicks). The live page's H1 is the bare
     * brand name, so the replacement's H1 must carry both terms.
     */
    public function test_the_h1_carries_the_brand_and_the_primary_keyword(): void
    {
        preg_match('#<h1[^>]*>(.*?)</h1>#s', $this->get('/v2')->getContent(), $m);

        $this->assertNotEmpty($m, 'no h1 on the page');
        $h1 = strtolower(strip_tags($m[1]));

        $this->assertStringContainsString('wetodrive', $h1, 'h1 lost the brand term');
        $this->assertStringContainsString('wetransfer', $h1, 'h1 lost the wetransfer term');
        $this->assertStringContainsString('google drive', $h1, 'h1 lost the google drive term');
    }

    public function test_it_mentions_the_brand_at_least_as_often_as_the_live_page(): void
    {
        $count = fn ($html) => substr_count(
            strtolower(preg_replace('#<script.*?</script>|<style.*?</style>#s', ' ', $html)),
            'wetodrive'
        );

        // Brand prominence is the signal behind the #1 ranking; the redesign must
        // not quietly thin it out.
        $this->assertGreaterThanOrEqual(
            $count($this->get('/')->getContent()),
            $count($this->get('/v2')->getContent()),
            'v2 mentions the brand less often than the live homepage'
        );
    }

    public function test_it_links_every_seo_landing_page_with_the_same_anchor_text(): void
    {
        $html = $this->get('/v2')->assertOk()->getContent();

        foreach (self::SEO_LINKS as $path => $anchor) {
            $this->assertStringContainsString($path, $html, "lost the internal link to {$path}");
            $this->assertStringContainsString($anchor, $html, "lost the anchor text '{$anchor}'");
        }
    }

    public function test_it_carries_the_live_pages_keyword_copy(): void
    {
        $html = $this->get('/v2')->assertOk()->getContent();

        foreach (self::KEYWORD_COPY as $phrase) {
            $this->assertStringContainsString($phrase, $html, "lost keyword copy: {$phrase}");
        }
    }

    public function test_it_has_the_meta_the_live_homepage_lacks(): void
    {
        $this->get('/v2')
            ->assertSee('name="description"', false)
            ->assertSee('name="keywords"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_the_structured_data_is_valid_json(): void
    {
        // Blade eats a bare @context/@type, so this guards the @@ escaping.
        preg_match(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $this->get('/v2')->getContent(),
            $m
        );

        $this->assertNotEmpty($m, 'no JSON-LD block found');
        $data = json_decode(trim($m[1]), true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD did not parse');
        $this->assertSame('SoftwareApplication', $data['@type']);
    }

    public function test_the_transfer_form_is_wired_to_the_shared_script(): void
    {
        $r = $this->actingAs(User::factory()->create())->get('/v2')->assertOk();

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

    public function test_stats_are_hidden_rather_than_showing_zero(): void
    {
        // An empty db must not advertise "0 transfers".
        $this->get('/v2')->assertOk()->assertDontSee('Transfers delivered to Drive', false);
    }

    public function test_the_live_homepage_still_works(): void
    {
        $this->get('/')->assertOk();
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('transferForm', false);
    }
}
