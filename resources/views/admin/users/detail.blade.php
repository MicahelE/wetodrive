@extends('admin.layout')

@section('title', 'User Details')

@section('content')
<a href="{{ route('admin.users') }}" class="back-link">← Back to Users</a>

<h2>User Details: {{ $user->name }}</h2>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
    <div class="stat-card">
        <h3>User Information</h3>
        <table class="table">
            <tr>
                <td><strong>Name:</strong></td>
                <td>{{ $user->name }}</td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <td><strong>Role:</strong></td>
                <td>
                    <span class="badge badge-{{ $user->role === 'admin' ? 'danger' : 'info' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Country:</strong></td>
                <td>{{ $user->country_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Total Transfers:</strong></td>
                <td>{{ number_format($user->total_transfers) }}</td>
            </tr>
            <tr>
                <td><strong>Last Transfer:</strong></td>
                <td>{{ $user->last_transfer_at ? $user->last_transfer_at->format('M j, Y H:i') : 'Never' }}</td>
            </tr>
            <tr>
                <td><strong>Joined:</strong></td>
                <td>{{ $user->created_at->format('M j, Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="stat-card">
        <h3>Current Subscription</h3>
        @if($user->activeSubscription)
            <table class="table">
                <tr>
                    <td><strong>Plan:</strong></td>
                    <td>{{ $user->activeSubscription->subscriptionPlan->name }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <span class="badge badge-{{ $user->activeSubscription->status === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($user->activeSubscription->status) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Provider:</strong></td>
                    <td>{{ ucfirst($user->activeSubscription->payment_provider) }}</td>
                </tr>
                <tr>
                    <td><strong>Amount Paid:</strong></td>
                    <td>{{ $user->activeSubscription->getFormattedAmount() }}</td>
                </tr>
                <tr>
                    <td><strong>Started:</strong></td>
                    <td>{{ $user->activeSubscription->started_at->format('M j, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Expires:</strong></td>
                    <td>{{ $user->activeSubscription->expires_at->format('M j, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Transfers Used:</strong></td>
                    <td>{{ $user->activeSubscription->transfers_used }}</td>
                </tr>
            </table>
        @else
            <p>No active subscription (Free plan)</p>
        @endif
    </div>
</div>

<div class="stat-card">
    <h3>Subscription History</h3>
    @if($user->subscriptions->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Provider</th>
                    <th>Amount</th>
                    <th>Started</th>
                    <th>Expired</th>
                </tr>
            </thead>
            <tbody>
                @foreach($user->subscriptions as $subscription)
                <tr>
                    <td>{{ $subscription->subscriptionPlan->name }}</td>
                    <td>
                        <span class="badge badge-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'cancelled' ? 'danger' : 'warning') }}">
                            {{ ucfirst($subscription->status) }}
                        </span>
                    </td>
                    <td>{{ ucfirst($subscription->payment_provider) }}</td>
                    <td>{{ $subscription->getFormattedAmount() }}</td>
                    <td>{{ $subscription->started_at->format('M j, Y') }}</td>
                    <td>{{ $subscription->expires_at ? $subscription->expires_at->format('M j, Y') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No subscription history</p>
    @endif
</div>

<div class="stat-card">
    <h3>Payment History</h3>
    @if($user->paymentTransactions->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                @foreach($user->paymentTransactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('M j, Y H:i') }}</td>
                    <td>{{ $transaction->getFormattedAmount() }}</td>
                    <td>{{ ucfirst($transaction->provider) }}</td>
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
    @else
        <p>No payment history</p>
    @endif
</div>

<div class="stat-card">
    <h3>Transfer History</h3>
    @if($transfers->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Files</th>
                    <th>Total Size</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $group)
                    @php
                        // Files from one WeTransfer link were imported together and
                        // share a batch_id. Anything else is a row on its own.
                        $files = $group->batch_id ? ($batchFiles[$group->batch_id] ?? collect()) : collect();
                        $many = $group->file_count > 1;
                    @endphp
                    <tr>
                        <td style="white-space: nowrap; vertical-align: top;">
                            {{ \Carbon\Carbon::parse($group->transferred_at)->format('M j, Y H:i') }}
                        </td>
                        <td>
                            @if($many)
                                {{-- Native disclosure: no JS to keep working. --}}
                                <details>
                                    <summary style="cursor: pointer;">
                                        <strong>{{ $group->file_count }} files</strong>
                                        <span style="color: #6b7280;">from one transfer</span>
                                    </summary>
                                    <ul style="margin: 10px 0 4px; padding-left: 20px; line-height: 1.7;">
                                        @foreach($files as $file)
                                            <li>
                                                {{ $file->filename ?? '—' }}
                                                <span style="color: #6b7280;">({{ $file->formatted_file_size }})</span>
                                                @if($file->google_drive_id)
                                                    <a href="https://drive.google.com/file/d/{{ $file->google_drive_id }}/view"
                                                       target="_blank" rel="noopener">open</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @else
                                {{-- Transfers recorded before the filename column existed have none. --}}
                                {{ $group->first_filename ?? '—' }}
                                @if($group->google_drive_id)
                                    <a href="https://drive.google.com/file/d/{{ $group->google_drive_id }}/view"
                                       target="_blank" rel="noopener">open</a>
                                @endif
                            @endif
                        </td>
                        <td style="white-space: nowrap; vertical-align: top;">
                            {{ \App\Models\Transfer::formatSize($group->total_size) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 15px;">
            {{ $transfers->links() }}
        </div>
    @else
        <p>No transfer history</p>
    @endif
</div>

<div style="margin-top: 20px;">
    @if(!$user->activeSubscription)
        <form method="POST" action="{{ route('admin.users.grant-trial', $user) }}" style="display: inline;" onsubmit="return confirm('Grant a free trial transfer (3GB) to this user?')">
            @csrf
            <button type="submit" class="btn btn-info">
                Grant Trial{{ $user->has_used_trial_transfer ? '' : ' (Already Available)' }}
            </button>
        </form>
    @endif

    @if($user->role === 'user')
        <form method="POST" action="{{ route('admin.users.make-admin', $user) }}" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-success">Make Admin</button>
        </form>
    @elseif($user->role === 'admin' && $user->id !== Auth::id())
        <form method="POST" action="{{ route('admin.users.remove-admin', $user) }}" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-warning">Remove Admin</button>
        </form>
    @endif

    @if($user->id !== Auth::id())
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display: inline;"
              onsubmit="return confirm('Permanently delete {{ $user->email }}? This removes their account, transfers, and subscriptions and cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete Account</button>
        </form>
    @endif
</div>
@endsection
