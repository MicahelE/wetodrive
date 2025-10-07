<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subscription_plans')->insert([
            [
                'name' => 'Free',
                'slug' => 'free',
                'price_ngn' => 0,
                'price_usd' => 0,
                'transfer_limit' => 5,
                'max_file_size' => 100 * 1024 * 1024, // 100MB
                'features' => json_encode([
                    '5 transfers per month',
                    '100MB file size limit',
                    'Basic transfer speed',
                    'Community support'
                ]),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_ngn' => 5000,
                'price_usd' => 10,
                'transfer_limit' => 100,
                'max_file_size' => 2 * 1024 * 1024 * 1024, // 2GB
                'features' => json_encode([
                    '100 transfers per month',
                    '2GB file size limit',
                    'Faster transfer speeds',
                    'Email support',
                    'Transfer history for 30 days'
                ]),
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price_ngn' => 50000,
                'price_usd' => 80,
                'transfer_limit' => null, // unlimited
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
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
