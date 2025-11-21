<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate XML sitemap for the application';

    public function handle()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Get the base URL from config
        $baseUrl = config('app.url');
        
        // Define public routes that should be in sitemap
        $publicRoutes = [
            [
                'url' => $baseUrl,
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ],
            [
                'url' => $baseUrl . '/pricing',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.9'
            ],
            [
                'url' => $baseUrl . '/wetransfer-pricing',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ],
            [
                'url' => $baseUrl . '/wetransfer-send-files',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ],
            [
                'url' => $baseUrl . '/wetransfer-upload',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ],
            [
                'url' => $baseUrl . '/wetransfer-free',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ],
            [
                'url' => $baseUrl . '/wetransfer-alternative',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.9'
            ],
            [
                'url' => $baseUrl . '/save-to-google-drive',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.9'
            ],
            [
                'url' => $baseUrl . '/help',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ],
            [
                'url' => $baseUrl . '/contact',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.6'
            ],
            [
                'url' => $baseUrl . '/privacy',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.5'
            ],
            [
                'url' => $baseUrl . '/terms',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.5'
            ],
            [
                'url' => $baseUrl . '/auth/google',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.5'
            ],
            [
                'url' => $baseUrl . '/sitemap.xml',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.3'
            ]
        ];

        // Add each route to sitemap
        foreach ($publicRoutes as $route) {
            $sitemap .= '  <url>' . "\n";
            $sitemap .= '    <loc>' . $route['url'] . '</loc>' . "\n";
            $sitemap .= '    <lastmod>' . $route['lastmod'] . '</lastmod>' . "\n";
            $sitemap .= '    <changefreq>' . $route['changefreq'] . '</changefreq>' . "\n";
            $sitemap .= '    <priority>' . $route['priority'] . '</priority>' . "\n";
            $sitemap .= '  </url>' . "\n";
        }

        $sitemap .= '</urlset>';

        // Write sitemap to public directory
        $sitemapPath = public_path('sitemap.xml');
        File::put($sitemapPath, $sitemap);

        $this->info('Sitemap generated successfully at: ' . $sitemapPath);
        
        return 0;
    }
}