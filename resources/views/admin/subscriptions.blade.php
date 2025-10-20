@extends('admin.layout')

@section('title', 'Subscriptions Management')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Subscriptions Management</h2>
    <div style="display: flex; gap: 10px;">
        <form method="GET" style="display: flex; gap: 10px;">
            <select name="status" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
            <select name="provider" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">All Providers</option>
                <option value="paystack" {{ request('provider') === 'paystack' ? 'selected' : '' }}>Paystack</option>
                <option value="lemonsqueezy" {{ request('provider') === 'lemonsqueezy' ? 'selected' : '' }}>LemonSqueezy</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>
</div>

<table class="table">
    <thead>
        <tr>
            <th>User</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Provider</th>
            <th>Amount</th>
            <th>Started</th>
            <th>Expires</th>
            <th>Transfers Used</th>
        </tr>
    </thead>
    <tbody>
        @foreach($subscriptions as $subscription)
        <tr>
            <td>
                <div>
                    <strong>{{ $subscription->user->name }}</strong><br>
                    <small style="color: #666;">{{ $subscription->user->email }}</small>
                </div>
            </td>
            <td>{{ $subscription->subscriptionPlan->name }}</td>
            <td>
                <span class="badge badge-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'cancelled' ? 'danger' : 'warning') }}">
                    {{ ucfirst($subscription->status) }}
                </span>
            </td>
            <td>
                <span class="badge badge-info">{{ ucfirst($subscription->payment_provider) }}</span>
            </td>
            <td>{{ $subscription->getFormattedAmount() }}</td>
            <td>{{ $subscription->started_at->format('M j, Y') }}</td>
            <td>{{ $subscription->expires_at ? $subscription->expires_at->format('M j, Y') : 'N/A' }}</td>
            <td>{{ $subscription->transfers_used }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 20px;">
    {{ $subscriptions->links() }}
</div>
@endsection
