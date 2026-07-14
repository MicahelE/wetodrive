<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Suppression flag: set by the signed unsubscribe link. Marketing email
            // must skip these users; transactional email still goes out.
            $table->boolean('email_opt_out')->default(false)->after('check_in_email_sent');

            // Per-campaign dedupe, same pattern as check_in_email_sent — makes a
            // re-run of emails:win-back a no-op instead of a second email.
            $table->boolean('winback_email_sent')->default(false)->after('email_opt_out');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_opt_out', 'winback_email_sent']);
        });
    }
};
