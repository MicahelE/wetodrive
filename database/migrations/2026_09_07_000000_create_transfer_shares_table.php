<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A share lets someone else pull the sharer's WeTransfer link into their own
 * Drive, under the sharer's plan limits rather than their own. The point is the
 * recipient: they get a transfer they could never run themselves, then meet
 * their own limit next time, which is the moment to sell them a plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the sharer
            $table->string('token', 64)->unique();

            // What is being shared. Copied rather than referenced: a share can be
            // made for a link the sharer has not transferred themselves.
            $table->text('wetransfer_url');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('total_size')->nullable();
            $table->unsignedInteger('file_count')->nullable();

            // Null when the sharer copied the link instead of sending an email.
            $table->string('recipient_email')->nullable();

            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            // WeTransfer links die after 7 days, so a share cannot outlive one.
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('share_limit')->default(0)->after('transfer_limit');
        });

        // Free is once, ever, like the 3GB trial. Paid resets each period.
        foreach (['free' => 1, 'pro' => 2, 'premium' => 3, 'ultra' => 5] as $slug => $limit) {
            DB::table('subscription_plans')->where('slug', $slug)->update(['share_limit' => $limit]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_shares');

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('share_limit');
        });
    }
};
