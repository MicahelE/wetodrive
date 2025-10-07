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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('provider', ['paystack', 'lemonsqueezy']);
            $table->string('provider_reference')->unique(); // Provider's transaction reference
            $table->enum('type', ['subscription', 'renewal', 'upgrade', 'downgrade', 'refund']);
            $table->enum('status', ['pending', 'success', 'failed', 'refunded']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('payment_method')->nullable(); // card, bank transfer, etc
            $table->json('provider_response')->nullable(); // Full response from provider
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('provider_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
