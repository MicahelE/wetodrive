<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsService
{
    public function getTotalUsers(): int
    {
        return User::count();
    }

    public function getActiveSubscriptions(): int
    {
        return UserSubscription::where('status', 'active')->count();
    }

    public function getTotalRevenue(): array
    {
        return PaymentTransaction::where('status', 'success')
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();
    }

    public function getMonthlyRevenue(): array
    {
        return PaymentTransaction::where('status', 'success')
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();
    }

    public function getUserGrowth(int $days = 30): array
    {
        return User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getSubscriptionTierDistribution(): array
    {
        return User::select('subscription_tier', DB::raw('count(*) as count'))
            ->groupBy('subscription_tier')
            ->get()
            ->toArray();
    }

    public function getRevenueByMonth(int $months = 12): array
    {
        return PaymentTransaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, currency, SUM(amount) as revenue')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month', 'currency')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getPaymentProviderStats(): array
    {
        return PaymentTransaction::selectRaw('provider, status, COUNT(*) as count')
            ->groupBy('provider', 'status')
            ->get()
            ->toArray();
    }

    public function getRecentActivity(int $limit = 10): array
    {
        $users = User::latest()->limit($limit)->get();
        $transactions = PaymentTransaction::where('status', 'success')
            ->latest()
            ->limit($limit)
            ->with('user')
            ->get();

        return [
            'recent_users' => $users,
            'recent_transactions' => $transactions,
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'total_users' => $this->getTotalUsers(),
            'active_subscriptions' => $this->getActiveSubscriptions(),
            'revenue_by_currency' => $this->getTotalRevenue(),
            'monthly_revenue_by_currency' => $this->getMonthlyRevenue(),
            'user_growth' => $this->getUserGrowth(30),
            'subscription_tiers' => $this->getSubscriptionTierDistribution(),
            'revenue_by_month' => $this->getRevenueByMonth(12),
            'payment_providers' => $this->getPaymentProviderStats(),
        ];
    }
}
