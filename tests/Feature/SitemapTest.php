<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * sitemap:generate runs on every deploy, so a deploy that adds no page must not
 * move every <lastmod> to that day, and one that does must pick the page up.
 */
class SitemapTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        // Never the real public/sitemap.xml, which is tracked in git.
        $this->dir = sys_get_temp_dir() . '/sitemap-test-' . uniqid();
        File::ensureDirectoryExists($this->dir);
        $this->app->usePublicPath($this->dir);

        config(['app.url' => 'https://wetodrive.com', 'services.dropbox.client_id' => null]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_an_unchanged_url_list_leaves_the_file_and_its_dates_alone(): void
    {
        $this->artisan('sitemap:generate')->assertSuccessful();
        $path = $this->dir . '/sitemap.xml';

        // As if it was generated months ago.
        File::put($path, str_replace('<lastmod>' . now()->format('Y-m-d'), '<lastmod>2025-11-21', File::get($path)));

        $this->artisan('sitemap:generate')->expectsOutputToContain('unchanged')->assertSuccessful();

        $this->assertStringContainsString('<lastmod>2025-11-21</lastmod>', File::get($path));
        $this->assertStringNotContainsString('<lastmod>' . now()->format('Y-m-d'), File::get($path));
    }

    public function test_a_new_page_rewrites_it_as_a_complete_file(): void
    {
        $this->artisan('sitemap:generate')->assertSuccessful();
        $path = $this->dir . '/sitemap.xml';
        $this->assertStringNotContainsString('/dropbox', File::get($path));

        config(['services.dropbox.client_id' => 'key']);
        $this->artisan('sitemap:generate')->expectsOutputToContain('generated successfully')->assertSuccessful();

        $this->assertStringContainsString('<loc>https://wetodrive.com/dropbox</loc>', File::get($path));
        $this->assertNotFalse(simplexml_load_string(File::get($path)), 'the sitemap should be valid XML');
        $this->assertFileDoesNotExist($path . '.tmp');
    }
}
