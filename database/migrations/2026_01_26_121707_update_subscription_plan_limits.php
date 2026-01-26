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
        // Update Pro plan: 2GB -> 5GB
        DB::table('subscription_plans')
            ->where('slug', 'pro')
            ->update([
                'max_file_size' => 5 * 1024 * 1024 * 1024, // 5GB
                'features' => json_encode([
                    '100 transfers per month',
                    '5GB file size limit',
                    'Faster transfer speeds',
                    'Email support',
                    'Transfer history for 30 days'
                ]),
                'updated_at' => now(),
            ]);

        // Update Premium plan: 5GB -> 100GB
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert Pro plan: 5GB -> 2GB
        DB::table('subscription_plans')
            ->where('slug', 'pro')
            ->update([
                'max_file_size' => 2 * 1024 * 1024 * 1024, // 2GB
                'features' => json_encode([
                    '100 transfers per month',
                    '2GB file size limit',
                    'Faster transfer speeds',
                    'Email support',
                    'Transfer history for 30 days'
                ]),
                'updated_at' => now(),
            ]);

        // Revert Premium plan: 100GB -> 5GB
        DB::table('subscription_plans')
            ->where('slug', 'premium')
            ->update([
                'max_file_size' => 5 * 1024 * 1024 * 1024, // 5GB
                'features' => json_encode([
                    'Unlimited transfers',
                    '5GB file size limit',
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
