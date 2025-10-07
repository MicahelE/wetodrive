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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Pro, Premium
            $table->string('slug')->unique(); // free, pro, premium
            $table->decimal('price_ngn', 10, 2)->default(0);
            $table->decimal('price_usd', 10, 2)->default(0);
            $table->integer('transfer_limit')->nullable(); // null = unlimited
            $table->bigInteger('max_file_size'); // in bytes
            $table->json('features')->nullable(); // Additional features as JSON
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
