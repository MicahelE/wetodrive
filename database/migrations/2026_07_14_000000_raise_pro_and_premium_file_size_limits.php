<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pro: 10GB -> 25GB
        DB::table('subscription_plans')
            ->where('slug', 'pro')
            ->update([
                'max_file_size' => 25 * 1024 * 1024 * 1024, // 25GB
                'features' => json_encode([
                    '100 transfers per month',
                    '25GB file size limit',
                    'Faster transfer speeds',
                    'Email support',
                    'Transfer history for 30 days'
                ]),
                'updated_at' => now(),
            ]);

        // Premium: 100GB -> 500GB
        DB::table('subscription_plans')
            ->where('slug', 'premium')
            ->update([
                'max_file_size' => 500 * 1024 * 1024 * 1024, // 500GB
                'features' => json_encode([
                    'Unlimited transfers',
                    '500GB file size limit',
                    'Priority transfer queue',
                    'Bulk transfer support',
                    'API access',
                    'Priority support',
                    'Advanced analytics',
                    'Transfer history forever'
                ]),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pro: 25GB -> 10GB
        DB::table('subscription_plans')
            ->where('slug', 'pro')
            ->update([
                'max_file_size' => 10 * 1024 * 1024 * 1024, // 10GB
                'features' => json_encode([
                    '100 transfers per month',
                    '10GB file size limit',
                    'Faster transfer speeds',
                    'Email support',
                    'Transfer history for 30 days'
                ]),
                'updated_at' => now(),
            ]);

        // Premium: 500GB -> 100GB
        DB::table('subscription_plans')
            ->where('slug', 'premium')
            ->update([
                'max_file_size' => 100 * 1024 * 1024 * 1024, // 100GB
                'features' => json_encode([
                    'Unlimited transfers',
                    '100GB file size limit',
                    'Priority transfer queue',
                    'Bulk transfer support',
                    'API access',
                    'Priority support',
                    'Advanced analytics',
                    'Transfer history forever'
                ]),
                'updated_at' => now(),
            ]);
    }
};
