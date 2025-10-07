<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('email'); // ISO country code
            $table->string('subscription_tier')->default('free')->after('google_refresh_token'); // free, pro, premium
            $table->foreignId('active_subscription_id')->nullable()->after('subscription_tier')
                ->constrained('user_subscriptions')->nullOnDelete();
            $table->integer('total_transfers')->default(0)->after('active_subscription_id'); // Lifetime transfer count
            $table->timestamp('last_transfer_at')->nullable()->after('total_transfers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_subscription_id']);
            $table->dropColumn([
                'country_code',
                'subscription_tier',
                'active_subscription_id',
                'total_transfers',
                'last_transfer_at'
            ]);
        });
    }
};
