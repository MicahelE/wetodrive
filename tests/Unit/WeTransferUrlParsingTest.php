<?php

namespace Tests\Unit;

use App\Services\StreamTransferService;
use PHPUnit\Framework\TestCase;

/**
 * The recipient-email link shape (an extra path segment) fed the recipient id
 * to WeTransfer as the security hash and 403'd every transfer for eight days.
 * These URLs are the real ones from the production logs; the expected values
 * were confirmed by replaying them against the live API, where hash plus
 * recipient_id returns 200 and either one alone returns 403.
 */
class WeTransferUrlParsingTest extends TestCase
{
    public function test_it_reads_the_hash_from_a_sender_page_link(): void
    {
        $this->assertSame(
            ['0abd696957055e5a085a6a4bf4ed22ba20260811115146', '34f978', null],
            StreamTransferService::parseDownloadUrl(
                'https://wetransfer.com/downloads/0abd696957055e5a085a6a4bf4ed22ba20260811115146/34f978?t_exp=1786708'
            )
        );
    }

    public function test_it_skips_the_recipient_id_in_an_email_link(): void
    {
        // Was returning the recipient id as the hash, and dropping it entirely
        // from the request body. The API needs it as its own field.
        $this->assertSame(
            [
                '0966002265ae2f1e20d7cec4730f6acc20260812123713',
                'c8a91b',
                '7178df309ea2ffa1a421341c8830f1ab20260812124010',
            ],
            StreamTransferService::parseDownloadUrl(
                'https://wetransfer.com/downloads/0966002265ae2f1e20d7cec4730f6acc20260812123713'
                .'/7178df309ea2ffa1a421341c8830f1ab20260812124010/c8a91b'
                .'?utm_source=wt_sendgrid&utm_medium=email&utm_campaign=TRN_DL_WTP_01'
            )
        );
    }

    public function test_it_handles_a_custom_subdomain(): void
    {
        $this->assertSame(
            [
                '48a7384b8a0684911768acce290c0fb920260814075648',
                '79835a',
                '260d5265cca73a30a4a252f30e18a82020260814081650',
            ],
            StreamTransferService::parseDownloadUrl(
                'https://tvv-productions.wetransfer.com/downloads/48a7384b8a0684911768acce290c0fb920260814075648'
                .'/260d5265cca73a30a4a252f30e18a82020260814081650/79835a'
            )
        );
    }

    public function test_it_rejects_a_url_that_is_not_a_download_link(): void
    {
        $this->expectException(\Exception::class);
        StreamTransferService::parseDownloadUrl('https://wetransfer.com/');
    }

    /**
     * Preview links were rejected outright even though they carry the same ids.
     * These two are the same transfer, from the 2026-05-22 logs, where the user
     * was bounced on the preview URL and retyped it as a download URL by hand.
     */
    public function test_it_points_a_preview_link_at_the_download_path(): void
    {
        $this->assertSame(
            'https://wetransfer.com/downloads/25869f2d9e5282c802524da99d4b75a020260516032144'
            .'/b29f19948c4dfb00d18cb940aaa66b4120260516060432/295b8a',
            StreamTransferService::normalizeDownloadUrl(
                'https://wetransfer.com/previews/25869f2d9e5282c802524da99d4b75a020260516032144'
                .'/b29f19948c4dfb00d18cb940aaa66b4120260516060432/295b8a'
            )
        );
    }

    public function test_it_normalises_previews_on_a_custom_subdomain(): void
    {
        $this->assertSame(
            'https://joejones.wetransfer.com/downloads/ec8e992d15091b9546f6c847c729530220260106203843/9fb6ef',
            StreamTransferService::normalizeDownloadUrl(
                'https://joejones.wetransfer.com/previews/ec8e992d15091b9546f6c847c729530220260106203843/9fb6ef'
            )
        );
    }

    public function test_it_leaves_everything_else_alone(): void
    {
        foreach ([
            'https://wetransfer.com/downloads/aaaa1111/bbbb22',
            'https://we.tl/t-SDzfcygzfirDLer1',
            'https://example.com/previews/whatever',
        ] as $url) {
            $this->assertSame($url, StreamTransferService::normalizeDownloadUrl($url));
        }
    }
}
