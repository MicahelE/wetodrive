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
        // Update Pro plan: 5GB -> 10GB
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert Pro plan: 10GB -> 5GB
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
    }
};
