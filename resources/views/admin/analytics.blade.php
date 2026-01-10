@extends('admin.layout')

@section('title', 'Analytics')

@section('content')
<h2>Analytics Dashboard</h2>

<div class="stats-grid">
    <div class="stat-card">
        <h3>User Growth (Last 30 Days)</h3>
        <div style="height: 200px; display: flex; align-items: end; gap: 2px; margin-top: 15px;">
            @foreach($analytics['user_growth'] as $day)
                <div style="background: #3498db; width: 20px; height: {{ max(5, ($day['count'] / max(1, $analytics['user_growth']->max('count'))) * 150) }}px; border-radius: 2px;" title="{{ $day['date'] }}: {{ $day['count'] }} users"></div>
            @endforeach
        </div>
    </div>

    <div class="stat-card">
        <h3>Revenue by Month (Last 12 Months)</h3>
        <div style="height: 200px; display: flex; align-items: end; gap: 2px; margin-top: 15px;">
            @foreach($analytics['revenue_by_month'] as $month)
                <div style="background: {{ $month['currency'] === 'NGN' ? '#27ae60' : '#3498db' }}; width: 20px; height: {{ max(5, ($month['revenue'] / max(1, $analytics['revenue_by_month']->max('revenue'))) * 150) }}px; border-radius: 2px;" title="{{ $month['month'] }} ({{ $month['currency'] }}): {{ $month['currency'] === 'NGN' ? '₦' : '$' }}{{ number_format($month['revenue'], $month['currency'] === 'NGN' ? 0 : 2) }}"></div>
            @endforeach
        </div>
        <div style="margin-top: 10px; font-size: 12px; color: #7f8c8d;">
            <span style="display: inline-block; width: 12px; height: 12px; background: #3498db; border-radius: 2px; margin-right: 5px;"></span> USD
            <span style="display: inline-block; width: 12px; height: 12px; background: #27ae60; border-radius: 2px; margin-left: 15px; margin-right: 5px;"></span> NGN
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
    <div class="stat-card">
        <h3>Subscription Distribution</h3>
        @if($analytics['subscription_distribution']->count() > 0)
            @foreach($analytics['subscription_distribution'] as $dist)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                    <span>{{ $dist->subscriptionPlan->name }}</span>
                    <span style="font-weight: bold;">{{ $dist->count }}</span>
                </div>
            @endforeach
        @else
            <p>No subscription data available</p>
        @endif
    </div>

    <div class="stat-card">
        <h3>Payment Provider Statistics</h3>
        @if($analytics['payment_provider_stats']->count() > 0)
            @foreach($analytics['payment_provider_stats'] as $stat)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                    <span>{{ ucfirst($stat->provider) }} - {{ ucfirst($stat->status) }}</span>
                    <span style="font-weight: bold;">{{ $stat->count }}</span>
                </div>
            @endforeach
        @else
            <p>No payment data available</p>
        @endif
    </div>
</div>

<div class="stat-card">
    <h3>Quick Stats</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">{{ $analytics['user_growth']->sum('count') }}</div>
            <div style="color: #7f8c8d; text-transform: uppercase; font-size: 12px;">New Users (30 days)</div>
        </div>
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">${{ number_format($analytics['total_revenue_by_currency']['USD'] ?? 0, 2) }}</div>
            <div style="color: #7f8c8d; text-transform: uppercase; font-size: 12px;">USD Revenue (12 months)</div>
        </div>
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">₦{{ number_format($analytics['total_revenue_by_currency']['NGN'] ?? 0, 0) }}</div>
            <div style="color: #7f8c8d; text-transform: uppercase; font-size: 12px;">NGN Revenue (12 months)</div>
        </div>
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">{{ $analytics['subscription_distribution']->sum('count') }}</div>
            <div style="color: #7f8c8d; text-transform: uppercase; font-size: 12px;">Active Subscriptions</div>
        </div>
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">{{ $analytics['payment_provider_stats']->sum('count') }}</div>
            <div style="color: #7f8c8d; text-transform: uppercase; font-size: 12px;">Total Transactions</div>
        </div>
    </div>
</div>
@endsection
