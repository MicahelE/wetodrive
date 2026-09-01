<?php

namespace Tests\Feature;

use App\Http\Controllers\TransferController;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ultra ($120, 1TB) has to exist in the plans table AND have a Polar product id
 * keyed by its slug, or checkout throws "No Polar product ID configured".
 */
class UltraPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_the_ultra_plan(): void
    {
        $ultra = SubscriptionPlan::where('slug', 'ultra')->firstOrFail();

        $this->assertSame(1024 * 1024 * 1024 * 1024, (int) $ultra->max_file_size);
        $this->assertNull($ultra->transfer_limit);
        $this->assertEquals(120, $ultra->price_usd);
        $this->assertTrue((bool) $ultra->is_active);
    }

    public function test_polar_product_ids_are_keyed_by_every_paid_slug(): void
    {
        foreach (SubscriptionPlan::active()->where('price_usd', '>', 0)->pluck('slug') as $slug) {
            $this->assertArrayHasKey($slug, (array) config('polar.product_ids'));
        }
    }

    public function test_a_file_over_the_premium_cap_recommends_ultra(): void
    {
        SubscriptionPlan::create([
            'name' => 'Premium', 'slug' => 'premium', 'price_ngn' => 50000, 'price_usd' => 80,
            'transfer_limit' => null, 'max_file_size' => 500 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 3,
        ]);

        $m = new \ReflectionMethod(TransferController::class, 'recommendPlanFor');
        $m->setAccessible(true);

        $plan = $m->invoke(new TransferController(), 700 * 1024 * 1024 * 1024);

        $this->assertSame('ultra', $plan?->slug);
    }
}
