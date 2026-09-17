<?php

namespace App\Support;

/**
 * Content for the "WeTransfer vs ..." pages, rendered by seo/compare.
 *
 * Data rather than two hand-built views: the pages share every section, so
 * only the words differ. Prices and limits were checked against each
 * provider's own pages in September 2026 (listed under sources). They change,
 * so update CHECKED when you re-verify them.
 */
class ComparisonPages
{
    public const CHECKED = 'September 2026';

    private const WETRANSFER_FREE = 'Up to 10 transfers or 3 GB in total every 30 days';

    private const WETRANSFER_EXPIRY = 'Transfers expire, after a few days on the free plan';

    public static function for(string $page): array
    {
        return match ($page) {
            'google-drive' => self::googleDrive(),
            'dropbox' => self::dropbox(),
        };
    }

    private static function googleDrive(): array
    {
        return [
            'other' => 'Google Drive',
            'title' => 'WeTransfer vs Google Drive (2026): Which Should You Use for Large Files?',
            'description' => 'WeTransfer vs Google Drive compared: free limits, file sizes, how long files last, sharing and price. Plus how to save a WeTransfer straight into Google Drive without downloading it.',
            'keywords' => 'wetransfer vs google drive, google drive vs wetransfer, wetransfer or google drive, wetransfer to google drive, wetransfer dropbox google drive',
            'canonical' => route('seo.vs-google-drive'),
            'h1' => 'WeTransfer vs Google Drive',
            'tagline' => 'One sends files, the other keeps them. How they compare in 2026, when to use each, and how to get a WeTransfer into your Drive without downloading it first.',
            'short_answer' => 'Use WeTransfer to send someone a large file once. Use Google Drive to keep files, organise them and share them for as long as you need. If someone sends you a WeTransfer you want to keep, save it into Google Drive before the link expires.',
            'rows' => [
                ['What it is', 'A service for sending files', 'Cloud storage with sharing'],
                ['Free plan', self::WETRANSFER_FREE, '15 GB of storage, shared with Gmail and Google Photos'],
                ['How long files last', self::WETRANSFER_EXPIRY, 'Until you delete them'],
                ['Largest single file', 'Up to 1 TB per transfer on the Ultimate plan', 'Up to 5 TB, if you have the storage for it'],
                ['Recipient needs an account', 'No', 'No, when you share with anyone who has the link'],
                ['Organising and search', 'None: files arrive as a download', 'Folders, search, previews and version history'],
                ['Working together', 'None', 'Shared folders, comments, Docs and Sheets'],
                ['Paid storage', 'Ultimate includes up to 2 TB', '100 GB for $1.99 a month, 2 TB for $9.99 a month (US prices)'],
            ],
            'use_wetransfer' => [
                'You are sending a large file to someone once, and it does not need to live in anyone\'s storage.',
                'The person receiving it should not need an account or an app.',
                'You want a quick download page rather than a folder to manage.',
            ],
            'use_other' => [
                'You need the files after the WeTransfer link has expired.',
                'You want folders, search and previews, or to share the same folder again and again.',
                'You work on the files with other people in Docs, Sheets or with comments.',
            ],
            'both_heading' => 'Use them together: save a WeTransfer straight into Google Drive',
            'both_intro' => 'Most people do not choose one or the other. Clients and collaborators send WeTransfers, and the files need to end up in Drive. The usual way is to download everything to your computer and upload it again. WetoDrive skips that step.',
            'steps' => [
                ['Copy the link', 'Take the WeTransfer link from the email or message. Short we.tl links and full wetransfer.com links both work.'],
                ['Paste it into WetoDrive', 'Sign in with Google, paste the link and, if you like, name a folder such as Clients/Acme.'],
                ['Close the tab', 'The files go straight from WeTransfer into your Drive, with nothing downloaded to your device. You get an email when they land.'],
            ],
            'cta_url' => auth()->check() ? route('home') : route('auth.google'),
            'cta_label' => 'Save a WeTransfer to Google Drive',
            'faqs' => [
                ['Is WeTransfer or Google Drive better for large files?', 'For sending a large file once to someone who does not need to keep it in your account, WeTransfer is simpler. For keeping large files and sharing them over time, Google Drive is better, because nothing expires. Google Drive\'s free 15 GB is shared with Gmail and Google Photos, so very large files usually need a paid plan.'],
                ['What is the difference between WeTransfer and Google Drive?', 'WeTransfer sends files: you upload them, share a link, and the transfer expires. Google Drive stores files: they stay in your account until you delete them, and you can organise, search, preview and share them whenever you want.'],
                ['Do WeTransfer files expire?', 'Yes. Free transfers expire after a few days, and paid plans such as Ultimate let you set your own expiration. Once a transfer expires the link stops working, so save anything you want to keep somewhere permanent before then.'],
                ['Can I save a WeTransfer directly to Google Drive?', 'Yes. Paste the WeTransfer link into WetoDrive and the files are saved straight into your Google Drive, without being downloaded to your computer or phone first.'],
                ['How much does Google Drive cost?', 'Every Google account includes 15 GB free, shared across Drive, Gmail and Google Photos. In the US, 100 GB costs $1.99 a month and 2 TB costs $9.99 a month. Prices vary by country.'],
                ['Does the person sending the files need to do anything different?', 'No. They send an ordinary WeTransfer. You paste the link they send you into WetoDrive.'],
            ],
            'sources' => [
                'WeTransfer plan limits' => 'https://wetransfer.com/help-center/subscriptions/plan-limits',
                'Google One plans' => 'https://one.google.com/about/plans',
                'Google Drive file size limits' => 'https://support.google.com/drive/answer/37603',
            ],
            'related' => ['WeTransfer vs Dropbox', route('seo.vs-dropbox')],
        ];
    }

    private static function dropbox(): array
    {
        $dropboxLive = (bool) config('services.dropbox.client_id');

        return [
            'other' => 'Dropbox',
            'title' => 'WeTransfer vs Dropbox (2026): Differences, Limits and Which to Use',
            'description' => 'WeTransfer vs Dropbox compared: free limits, transfer sizes, expiry, storage and price. Plus how to save a WeTransfer straight into Dropbox without downloading it.',
            'keywords' => 'wetransfer vs dropbox, dropbox vs wetransfer, dropbox transfer vs wetransfer, wetransfer to dropbox, save wetransfer to dropbox',
            'canonical' => route('seo.vs-dropbox'),
            'h1' => 'WeTransfer vs Dropbox',
            'tagline' => 'A sending service and a storage service. How they compare in 2026, where Dropbox Transfer fits in, and how to get a WeTransfer into your Dropbox without downloading it first.',
            'short_answer' => 'Use WeTransfer to send someone a large file once with no account needed. Use Dropbox to keep files, organise them and share folders over time. If someone sends you a WeTransfer you want to keep, save it into Dropbox before the link expires.',
            'rows' => [
                ['What it is', 'A service for sending files', 'Cloud storage with sharing, plus its own sending tool, Dropbox Transfer'],
                ['Free plan', self::WETRANSFER_FREE, 'Dropbox Basic: 2 GB of storage'],
                ['How long files last', self::WETRANSFER_EXPIRY, 'Stored files stay until you delete them. Dropbox Transfer links expire after 7 or 30 days, depending on the plan'],
                ['Largest single send', 'Up to 1 TB per transfer on the Ultimate plan', 'Dropbox Transfer: up to 50 GB on Plus, 100 GB on Standard and Advanced'],
                ['Recipient needs an account', 'No', 'No, for shared links and transfers'],
                ['Organising and search', 'None: files arrive as a download', 'Folders, search, previews and version history'],
                ['Paid storage', 'Ultimate includes up to 2 TB', 'Plus: 2 TB from $9.99 a month'],
            ],
            'use_wetransfer' => [
                'You are sending a large file to someone once, and it does not need to live in anyone\'s storage.',
                'The person receiving it should not need an account or an app.',
                'You are on Dropbox Basic, where 2 GB of storage is too little to send big deliveries from.',
            ],
            'use_other' => [
                'You need the files after the WeTransfer link has expired.',
                'You share the same folders with clients or a team again and again.',
                'You already pay for Dropbox, where Dropbox Transfer covers most one-off sends too.',
            ],
            'both_heading' => 'Use them together: save a WeTransfer straight into Dropbox',
            'both_intro' => 'Dropbox cannot open a WeTransfer link by itself, so the usual way is to download everything to your computer and upload it again. WetoDrive moves the files from WeTransfer into your Dropbox for you.',
            'steps' => [
                ['Copy the link', 'Take the WeTransfer link from the email or message. Short we.tl links and full wetransfer.com links both work.'],
                ['Continue with Dropbox', 'Allow WetoDrive on Dropbox\'s screen, paste the link and, if you like, name a folder such as Clients/Acme.'],
                ['Close the tab', 'Your free Dropbox space is checked first, then the files go straight into your Dropbox, with nothing downloaded to your device. You get an email when they land.'],
            ],
            // The Dropbox page only exists once Dropbox is configured.
            'cta_url' => $dropboxLive ? route('dropbox') : route('home'),
            'cta_label' => $dropboxLive ? 'Save a WeTransfer to Dropbox' : 'Try WetoDrive',
            'faqs' => [
                ['Is WeTransfer or Dropbox better for sending large files?', 'For sending a large file once with no account needed, WeTransfer is simpler, and its Ultimate plan sends up to 1 TB per transfer. If you already pay for Dropbox, Dropbox Transfer sends up to 50 GB on Plus and 100 GB on Standard and Advanced, and keeps everything in one place.'],
                ['What is the difference between WeTransfer and Dropbox Transfer?', 'Both send files with a link that expires. WeTransfer is a standalone sending service with a free plan. Dropbox Transfer is part of a Dropbox plan: it sends files from your Dropbox, and its links expire after 7 or 30 days depending on the plan.'],
                ['Can I save a WeTransfer directly to Dropbox?', 'Dropbox cannot open a WeTransfer link by itself. With WetoDrive you paste the link and the files are saved straight into your Dropbox, without being downloaded to your computer or phone first.'],
                ['How much free storage does Dropbox give you?', 'Dropbox Basic includes 2 GB. That fills up quickly with video or photo deliveries, which is why WetoDrive checks your free Dropbox space before fetching anything.'],
                ['Do WeTransfer files expire?', 'Yes. Free transfers expire after a few days, and paid plans such as Ultimate let you set your own expiration. Once a transfer expires the link stops working, so save anything you want to keep before then.'],
                ['Do I need a Google account to save a WeTransfer to Dropbox?', 'No. Continue with Dropbox and your WetoDrive account is set up from your Dropbox account.'],
            ],
            'sources' => [
                'WeTransfer plan limits' => 'https://wetransfer.com/help-center/subscriptions/plan-limits',
                'Dropbox plans' => 'https://www.dropbox.com/plans',
                'Dropbox Transfer help' => 'https://help.dropbox.com/share/create-dropbox-transfer',
            ],
            'related' => ['WeTransfer vs Google Drive', route('seo.vs-google-drive')],
        ];
    }
}
