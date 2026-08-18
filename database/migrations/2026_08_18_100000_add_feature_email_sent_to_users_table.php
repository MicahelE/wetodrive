<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks who has had the folders / per-file announcement.
     *
     * The send is staged in batches, so this is what makes the second batch the
     * exact complement of the first and stops a re-run emailing anyone twice.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('feature_email_sent')->default(false)->after('winback_email_sent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('feature_email_sent');
        });
    }
};
