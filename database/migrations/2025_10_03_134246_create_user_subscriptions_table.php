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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('restrict');
            $table->enum('payment_provider', ['paystack', 'lemonsqueezy']);
            $table->string('provider_subscription_id')->nullable(); // Provider's subscription ID
            $table->enum('status', ['active', 'cancelled', 'expired', 'past_due']);
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->integer('transfers_used')->default(0); // Track usage this period
            $table->timestamp('period_resets_at')->nullable(); // When transfer count resets
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency', 3); // NGN or USD
            $table->json('metadata')->nullable(); // Store additional provider data
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('provider_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
