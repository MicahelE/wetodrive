<?php

namespace Tests\Feature;

use App\Http\Controllers\TransferController;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * When a file is too big we name the exact plan it needs, so users stop buying the
 * wrong tier (the mistake that cost us #382 and Alicia, who both bought Pro for a
 * file that needed Premium).
 */
class SizeAwareUpsellTest extends TestCase
{
    use RefreshDatabase;

    private function plans(): void
    {
        SubscriptionPlan::create([
            'name' => 'Free', 'slug' => 'free', 'price_ngn' => 0, 'price_usd' => 0,
            'transfer_limit' => 5, 'max_file_size' => 100 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 1,
        ]);
        SubscriptionPlan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price_ngn' => 5000, 'price_usd' => 10,
            'transfer_limit' => 100, 'max_file_size' => 25 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 2,
        ]);
        SubscriptionPlan::create([
            'name' => 'Premium', 'slug' => 'premium', 'price_ngn' => 50000, 'price_usd' => 80,
            'transfer_limit' => null, 'max_file_size' => 500 * 1024 * 1024 * 1024,
            'features' => [], 'is_active' => true, 'sort_order' => 3,
        ]);
    }

    private function invoke(string $method, array $args)
    {
        $m = new \ReflectionMethod(TransferController::class, $method);
        $m->setAccessible(true);

        return $m->invoke(new TransferController(), ...$args);
    }

    public function test_it_recommends_the_cheapest_plan_that_fits(): void
    {
        $this->plans();
        $gb = 1024 * 1024 * 1024;

        $this->assertSame('pro', $this->invoke('recommendPlanFor', [20 * $gb])?->slug);
        $this->assertSame('premium', $this->invoke('recommendPlanFor', [100 * $gb])?->slug);
        $this->assertNull($this->invoke('recommendPlanFor', [900 * $gb]), 'nothing fits a 900GB file');
    }

    public function test_the_payload_names_the_recommended_plan_for_a_serveable_file(): void
    {
        $this->plans();
        $user = User::factory()->create(['country_code' => 'US']);
        $gb = 1024 * 1024 * 1024;

        // 100GB file on a Pro (25GB) plan -> should point at Premium.
        $payload = $this->invoke('limitErrorPayload', [
            $user,
            ['size' => 100 * $gb, 'filename' => 'raw.zip'],
            25 * $gb,
        ]);

        $this->assertSame('premium', $payload['recommended_plan']);
        $this->assertSame('Premium', $payload['recommended_plan_name']);
        $this->assertStringContainsString('Premium', $payload['message']);
        $this->assertStringContainsString('recommended=premium', $payload['upgrade_url']);
    }

    public function test_the_payload_is_generic_when_no_plan_can_serve_the_file(): void
    {
        $this->plans();
        $user = User::factory()->create();
        $gb = 1024 * 1024 * 1024;

        $payload = $this->invoke('limitErrorPayload', [
            $user,
            ['size' => 900 * $gb, 'filename' => 'huge.zip'],
            500 * $gb,
        ]);

        $this->assertArrayNotHasKey('recommended_plan', $payload);
        $this->assertStringNotContainsString('recommended=', $payload['upgrade_url']);
        $this->assertStringContainsString('larger than any', $payload['message']);
    }

    public function test_pricing_page_highlights_only_the_recommended_plan(): void
    {
        $this->plans();
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->actingAs($user)
            ->get(route('subscription.pricing', ['recommended' => 'premium']))
            ->assertOk()
            ->assertSee('Recommended for your file');
    }

    public function test_pricing_page_shows_no_recommendation_badge_without_the_param(): void
    {
        $this->plans();
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->actingAs($user)
            ->get(route('subscription.pricing'))
            ->assertOk()
            ->assertDontSee('Recommended for your file');
    }
}
