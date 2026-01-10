<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
            'revenue_by_currency' => PaymentTransaction::where('status', 'success')
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->toArray(),
            'new_users_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'subscription_tiers' => User::select('subscription_tier', DB::raw('count(*) as count'))
                ->groupBy('subscription_tier')
                ->get(),
        ];

        $recent_users = User::with('activeSubscription.subscriptionPlan')
            ->latest()
            ->limit(10)
            ->get();

        $recent_transactions = PaymentTransaction::with('user', 'userSubscription.subscriptionPlan')
            ->where('status', 'success')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_users', 'recent_transactions'));
    }

    public function users(Request $request)
    {
        $query = User::with('activeSubscription.subscriptionPlan');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by subscription tier
        if ($request->filled('subscription_tier')) {
            $query->where('subscription_tier', $request->subscription_tier);
        }

        $users = $query->latest()->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function userDetail(User $user)
    {
        $user->load([
            'subscriptions.subscriptionPlan',
            'paymentTransactions',
            'activeSubscription.subscriptionPlan'
        ]);

        return view('admin.users.detail', compact('user'));
    }

    public function makeAdmin(Request $request, User $user)
    {
        $user->makeAdmin();

        return redirect()->back()->with('success', "{$user->name} has been promoted to admin.");
    }

    public function removeAdmin(Request $request, User $user)
    {
        // Prevent removing the last admin
        if (User::where('role', 'admin')->count() <= 1) {
            return redirect()->back()->with('error', 'Cannot remove the last admin.');
        }

        $user->removeAdmin();

        return redirect()->back()->with('success', "{$user->name} has been demoted from admin.");
    }

    public function subscriptions(Request $request)
    {
        $query = UserSubscription::with(['user', 'subscriptionPlan']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment provider
        if ($request->filled('provider')) {
            $query->where('payment_provider', $request->provider);
        }

        $subscriptions = $query->latest()->paginate(20);

        return view('admin.subscriptions', compact('subscriptions'));
    }

    public function transactions(Request $request)
    {
        $query = PaymentTransaction::with(['user', 'userSubscription.subscriptionPlan']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        $transactions = $query->latest()->paginate(20);

        return view('admin.transactions', compact('transactions'));
    }

    public function analytics()
    {
        $analytics = [
            'user_growth' => User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'revenue_by_month' => PaymentTransaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, currency, SUM(amount) as revenue')
                ->where('status', 'success')
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('month', 'currency')
                ->orderBy('month')
                ->get(),

            'total_revenue_by_currency' => PaymentTransaction::where('status', 'success')
                ->where('created_at', '>=', now()->subMonths(12))
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->toArray(),

            'subscription_distribution' => UserSubscription::selectRaw('subscription_plan_id, COUNT(*) as count')
                ->with('subscriptionPlan')
                ->where('status', 'active')
                ->groupBy('subscription_plan_id')
                ->get(),

            'payment_provider_stats' => PaymentTransaction::selectRaw('provider, status, COUNT(*) as count')
                ->groupBy('provider', 'status')
                ->get(),
        ];

        return view('admin.analytics', compact('analytics'));
    }
}