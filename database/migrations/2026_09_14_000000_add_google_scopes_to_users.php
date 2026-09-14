<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What Google actually granted at the last sign-in.
 *
 * Drive access is a tick-box on the consent screen and 53 of 138 sign-ins in
 * the fortnight to 14 Sep came back without it. Nothing noticed, so those users
 * were allowed to start transfers that could not possibly land: every file was
 * pulled from WeTransfer in full and only then refused by Drive.
 *
 * Nullable on purpose. Existing users predate this and their scopes are
 * unknown, which must read as "allowed" -- only a positively missing scope
 * blocks anything.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('google_scopes')->nullable()->after('google_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_scopes');
        });
    }
};
