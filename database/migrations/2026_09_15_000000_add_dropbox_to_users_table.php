<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dropbox_account_id')->nullable();
            // Text, not string: stored through the encrypted cast, which makes
            // a 64-character token several times longer.
            $table->text('dropbox_refresh_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dropbox_account_id', 'dropbox_refresh_token']);
        });
    }
};
