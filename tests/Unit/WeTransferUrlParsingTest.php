<?php

namespace Tests\Unit;

use App\Services\StreamTransferService;
use PHPUnit\Framework\TestCase;

/**
 * The recipient-email link shape (an extra path segment) fed the recipient id
 * to WeTransfer as the security hash and 403'd every transfer for eight days.
 * These URLs are the real ones from the production logs.
 */
class WeTransferUrlParsingTest extends TestCase
{
    public function test_it_reads_the_hash_from_a_sender_page_link(): void
    {
        $this->assertSame(
            ['0abd696957055e5a085a6a4bf4ed22ba20260811115146', '34f978'],
            StreamTransferService::parseDownloadUrl(
                'https://wetransfer.com/downloads/0abd696957055e5a085a6a4bf4ed22ba20260811115146/34f978?t_exp=1786708'
            )
        );
    }

    public function test_it_skips_the_recipient_id_in_an_email_link(): void
    {
        // Was returning 7178df...124010 (the recipient id) as the hash.
        $this->assertSame(
            ['0966002265ae2f1e20d7cec4730f6acc20260812123713', 'c8a91b'],
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
            ['48a7384b8a0684911768acce290c0fb920260814075648', '79835a'],
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
}
