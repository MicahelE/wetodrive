<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function pricing()
    {
        $metaData = [
            'title' => 'WeTransfer Pricing (2025): What\'s Free, What\'s Paid — and a Smarter Alternative',
            'description' => 'Complete breakdown of WeTransfer pricing in 2025. Compare Free vs Pro plans and discover WetoDrive - automatically save WeTransfer files to Google Drive.',
            'keywords' => 'wetransfer pricing, wetransfer cost, wetransfer free vs pro, wetransfer plans, wetransfer alternative',
            'canonical' => route('seo.pricing'),
        ];

        return view('seo.pricing', compact('metaData'));
    }

    public function sendFiles()
    {
        $metaData = [
            'title' => 'How to Send Files with WeTransfer — and Save Them Directly to Google Drive',
            'description' => 'Step-by-step guide to sending files with WeTransfer. Learn how to automatically save received files to Google Drive with WetoDrive.',
            'keywords' => 'wetransfer send files, how to send large files, wetransfer upload, send files online',
            'canonical' => route('seo.send-files'),
        ];

        return view('seo.send-files', compact('metaData'));
    }

    public function upload()
    {
        $metaData = [
            'title' => 'How to Upload Files to WeTransfer — and Automatically Save Them to Drive',
            'description' => 'Complete guide to uploading files on WeTransfer. Discover how WetoDrive automates saving received files directly to Google Drive.',
            'keywords' => 'wetransfer upload, wetransfer upload files, how to upload large files, wetransfer tutorial',
            'canonical' => route('seo.upload'),
        ];

        return view('seo.upload', compact('metaData'));
    }

    public function free()
    {
        $metaData = [
            'title' => 'WeTransfer Free: What You Get, What You Don\'t — and How to Extend It with WetoDrive',
            'description' => 'Everything about WeTransfer Free plan in 2025: limits, features, and how WetoDrive helps you keep files permanently in Google Drive.',
            'keywords' => 'wetransfer free, wetransfer free plan, wetransfer limitations, wetransfer storage limit',
            'canonical' => route('seo.free'),
        ];

        return view('seo.free', compact('metaData'));
    }

    public function alternative()
    {
        $metaData = [
            'title' => 'Best WeTransfer Alternative 2025: WetoDrive Automates Google Drive Saves',
            'description' => 'Looking for a WeTransfer alternative? WetoDrive automatically saves files from WeTransfer to Google Drive. No downloads, no manual uploads.',
            'keywords' => 'wetransfer alternative, file transfer alternative, google drive integration, automatic file transfer',
            'canonical' => route('seo.alternative'),
        ];

        return view('seo.alternative', compact('metaData'));
    }
}