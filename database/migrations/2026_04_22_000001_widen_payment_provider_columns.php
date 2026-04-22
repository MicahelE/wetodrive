<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('payment_provider', 32)->change();
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('provider', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->enum('payment_provider', ['paystack', 'lemonsqueezy'])->change();
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->enum('provider', ['paystack', 'lemonsqueezy'])->change();
        });
    }
};
