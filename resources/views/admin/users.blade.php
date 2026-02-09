@extends('admin.layout')

@section('title', 'Users Management')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Users Management</h2>
    <div style="display: flex; gap: 10px;">
        <form method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Search users..." value="{{ request('search') }}" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <select name="role" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">All Roles</option>
                <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Users</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admins</option>
            </select>
            <select name="subscription_tier" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">All Plans</option>
                <option value="free" {{ request('subscription_tier') === 'free' ? 'selected' : '' }}>Free</option>
                <option value="pro" {{ request('subscription_tier') === 'pro' ? 'selected' : '' }}>Pro</option>
                <option value="premium" {{ request('subscription_tier') === 'premium' ? 'selected' : '' }}>Premium</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Plan</th>
            <th>Country</th>
            <th>Transfers</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>
                <span class="badge badge-{{ $user->role === 'admin' ? 'danger' : 'info' }}">
                    {{ ucfirst($user->role) }}
                </span>
            </td>
            <td>
                <span class="badge badge-{{ $user->subscription_tier === 'free' ? 'warning' : 'success' }}">
                    {{ ucfirst($user->subscription_tier) }}
                </span>
            </td>
            <td>{{ $user->country_name ?? 'N/A' }}</td>
            <td>{{ number_format($user->total_transfers) }}</td>
            <td>{{ $user->created_at->format('M j, Y') }}</td>
            <td>
                <div style="display: flex; gap: 5px;">
                    <a href="{{ route('admin.users.detail', $user) }}" class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;">View</a>
                    @if($user->role === 'user')
                        <form method="POST" action="{{ route('admin.users.make-admin', $user) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success" style="padding: 4px 8px; font-size: 12px;">Make Admin</button>
                        </form>
                    @elseif($user->role === 'admin' && $user->id !== Auth::id())
                        <form method="POST" action="{{ route('admin.users.remove-admin', $user) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning" style="padding: 4px 8px; font-size: 12px;">Remove Admin</button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 20px;">
    {{ $users->links() }}
</div>
@endsection
