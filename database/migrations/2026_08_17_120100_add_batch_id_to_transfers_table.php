<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Groups the rows that came from one WeTransfer link.
     *
     * Added ahead of the per-file import that will produce many rows per link.
     * Like the filename column before it, this cannot be backfilled once the
     * transfers have happened, so it goes in before the rows exist rather than
     * after someone wishes it were there.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->uuid('batch_id')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};
