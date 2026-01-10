@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Users</h3>
        <div class="value">{{ number_format($stats['total_users']) }}</div>
    </div>
    <div class="stat-card">
        <h3>Active Subscriptions</h3>
        <div class="value">{{ number_format($stats['active_subscriptions']) }}</div>
    </div>
    <div class="stat-card">
        <h3>Revenue (USD)</h3>
        <div class="value">${{ number_format($stats['revenue_by_currency']['USD'] ?? 0, 2) }}</div>
    </div>
    <div class="stat-card">
        <h3>Revenue (NGN)</h3>
        <div class="value">₦{{ number_format($stats['revenue_by_currency']['NGN'] ?? 0, 0) }}</div>
    </div>
    <div class="stat-card">
        <h3>New Users This Month</h3>
        <div class="value">{{ number_format($stats['new_users_this_month']) }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <div class="stat-card">
        <h3>Recent Users</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recent_users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge badge-{{ $user->subscription_tier === 'free' ? 'warning' : 'success' }}">
                            {{ ucfirst($user->subscription_tier) }}
                        </span>
                    </td>
                    <td>{{ $user->created_at->format('M j, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="stat-card">
        <h3>Recent Transactions</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Provider</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recent_transactions as $transaction)
                <tr>
                    <td>{{ $transaction->user->name }}</td>
                    <td>{{ $transaction->getFormattedAmount() }}</td>
                    <td>
                        <span class="badge badge-info">{{ ucfirst($transaction->provider) }}</span>
                    </td>
                    <td>{{ $transaction->created_at->format('M j, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="stat-card">
    <h3>Subscription Tier Distribution</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
        @foreach($stats['subscription_tiers'] as $tier)
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">{{ $tier->count }}</div>
            <div style="color: #7f8c8d; text-transform: uppercase; font-size: 12px;">{{ ucfirst($tier->subscription_tier) }}</div>
        </div>
        @endforeach
    </div>
</div>
@endsection
