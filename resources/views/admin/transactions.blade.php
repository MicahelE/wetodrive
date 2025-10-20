@extends('admin.layout')

@section('title', 'Payment Transactions')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Payment Transactions</h2>
    <div style="display: flex; gap: 10px;">
        <form method="GET" style="display: flex; gap: 10px;">
            <select name="status" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">All Statuses</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
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
            <th>Date</th>
            <th>User</th>
            <th>Plan</th>
            <th>Amount</th>
            <th>Provider</th>
            <th>Status</th>
            <th>Reference</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $transaction)
        <tr>
            <td>{{ $transaction->created_at->format('M j, Y H:i') }}</td>
            <td>
                <div>
                    <strong>{{ $transaction->user->name }}</strong><br>
                    <small style="color: #666;">{{ $transaction->user->email }}</small>
                </div>
            </td>
            <td>{{ $transaction->userSubscription->subscriptionPlan->name ?? 'N/A' }}</td>
            <td>{{ $transaction->getFormattedAmount() }}</td>
            <td>
                <span class="badge badge-info">{{ ucfirst($transaction->provider) }}</span>
            </td>
            <td>
                <span class="badge badge-{{ $transaction->status === 'success' ? 'success' : ($transaction->status === 'failed' ? 'danger' : 'warning') }}">
                    {{ ucfirst($transaction->status) }}
                </span>
            </td>
            <td>{{ $transaction->provider_reference }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 20px;">
    {{ $transactions->links() }}
</div>
@endsection
