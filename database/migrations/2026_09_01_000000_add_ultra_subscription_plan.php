<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ultra: $120/mo, unlimited transfers, 1TB per transfer. Sits above Premium for
 * the studios whose camera-original deliveries run past the 500GB Premium cap.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('subscription_plans')->where('slug', 'ultra')->exists()) {
            return;
        }

        DB::table('subscription_plans')->insert([
            'name' => 'Ultra',
            'slug' => 'ultra',
            'price_ngn' => 75000,
            'price_usd' => 120,
            'transfer_limit' => null, // unlimited
            'max_file_size' => 1024 * 1024 * 1024 * 1024, // 1TB
            'features' => json_encode([
                'Unlimited transfers',
                '1TB file size limit',
                'Priority transfer queue',
                'Bulk transfer support',
                'API access',
                'Dedicated support',
                'Advanced analytics',
                'Transfer history forever',
            ]),
            'is_active' => true,
            'sort_order' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // ponytail: plain delete. Anyone already subscribed to Ultra would need
        // moving off it first, which is a support job, not a migration.
        DB::table('subscription_plans')->where('slug', 'ultra')->delete();
    }
};
