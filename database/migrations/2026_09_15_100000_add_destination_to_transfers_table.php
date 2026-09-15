<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            // Every transfer before Dropbox existed went to Drive, so the default
            // is also the correct backfill.
            $table->string('destination')->default('drive')->after('google_drive_id');
        });

        // Dropbox transfers made before this column existed stored a path, and
        // a Drive file id never starts with a slash.
        DB::table('transfers')->where('google_drive_id', 'like', '/%')->update(['destination' => 'dropbox']);
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('destination');
        });
    }
};
