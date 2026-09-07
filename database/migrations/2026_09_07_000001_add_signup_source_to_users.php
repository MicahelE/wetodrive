<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a signup came from, captured on their first page view.
 *
 * Until now this was only answerable by grepping Apache logs, which keep 14
 * days, so anyone who signed up earlier was unattributable. Two columns rather
 * than a parsed "channel": the raw values can always be reclassified later, and
 * a channel guessed at write time cannot be un-guessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signup_referrer', 512)->nullable()->after('country_code');

            // Path plus query string, so utm_* is captured without needing a
            // column per parameter before any campaign exists.
            $table->string('signup_landing', 512)->nullable()->after('signup_referrer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signup_referrer', 'signup_landing']);
        });
    }
};
