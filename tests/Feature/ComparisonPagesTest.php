<?php

namespace Tests\Feature;

use App\Support\ComparisonPages;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * "WeTransfer vs Google Drive" and "WeTransfer vs Dropbox" each get 100-1K
 * searches a month. Search Console showed the "vs Google Drive" impressions
 * split across five pages that were not about it, so these pin the page each
 * query should land on, and the links that point Google at it.
 */
class ComparisonPagesTest extends TestCase
{
    private const PAGES = [
        '/wetransfer-vs-google-drive' => ['WeTransfer vs Google Drive', 'Google Drive'],
        '/wetransfer-vs-dropbox' => ['WeTransfer vs Dropbox', 'Dropbox'],
    ];

    public function test_each_page_is_built_to_rank_for_its_comparison(): void
    {
        foreach (self::PAGES as $path => [$keyword, $other]) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringContainsString("<title>{$keyword}", $html);
            $this->assertMatchesRegularExpression('#<h1>' . preg_quote($keyword, '#') . '</h1>#', $html);
            $this->assertStringContainsString('<link rel="canonical" href="' . url($path) . '">', $html);
            $this->assertStringContainsString('<th scope="col">' . $other . '</th>', $html, "{$path} should compare against {$other}");

            preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
            $schema = json_decode($m[1] ?? '', true);
            $this->assertSame('FAQPage', $schema['@type'] ?? null, "{$path} FAQ markup should parse");

            // Every structured question is one a visitor can read on the page.
            foreach ($schema['mainEntity'] as $qa) {
                $this->assertStringContainsString('<summary>' . e($qa['name']) . '</summary>', $html);
            }
        }
    }

    public function test_the_dropbox_comparison_sends_people_to_the_dropbox_page_only_when_it_exists(): void
    {
        config(['services.dropbox.client_id' => 'key']);
        $this->assertSame(route('dropbox'), ComparisonPages::for('dropbox')['cta_url']);

        config(['services.dropbox.client_id' => null]);
        $this->assertSame(route('home'), ComparisonPages::for('dropbox')['cta_url']);
        $this->get('/wetransfer-vs-dropbox')->assertOk()->assertDontSee('href="' . url('/dropbox') . '"', false);
    }

    public function test_the_pages_that_were_splitting_the_impressions_now_link_to_them(): void
    {
        foreach (['/', '/save-to-google-drive', '/wetransfer-send-files', '/wetransfer-alternative', '/wetransfer-pricing'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            foreach (self::PAGES as $target => [$anchor]) {
                $this->assertStringContainsString('href="' . url($target) . '">' . $anchor . '</a>', $html, "{$path} should link to {$target}");
            }
        }
    }

    public function test_both_pages_are_in_the_sitemap(): void
    {
        $dir = sys_get_temp_dir() . '/compare-sitemap-' . uniqid();
        File::ensureDirectoryExists($dir);
        $this->app->usePublicPath($dir);
        config(['app.url' => 'https://wetodrive.com']);

        $this->artisan('sitemap:generate')->assertSuccessful();
        $xml = File::get($dir . '/sitemap.xml');
        File::deleteDirectory($dir);

        foreach (array_keys(self::PAGES) as $path) {
            $this->assertStringContainsString("<loc>https://wetodrive.com{$path}</loc>", $xml);
        }
    }
}
