<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The table only recorded a size and a timestamp, so transfer history read
     * "1.2 GB, 3 days ago" with nothing to click. Both columns are nullable
     * because the rows already in production cannot be backfilled: the filename
     * and the Drive id were never persisted anywhere.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('filename')->nullable()->after('user_id');
            $table->string('google_drive_id')->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['filename', 'google_drive_id']);
        });
    }
};
