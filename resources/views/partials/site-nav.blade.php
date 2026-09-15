{{-- ============ NAV ============ --}}
<nav class="nav">
    <div class="wrap nav-in">
        <a href="{{ route('home') }}" class="brand">
            @include('partials.logo-mark', ['size' => 30])
            <span class="brand-name">Weto<em>Drive</em></span>
        </a>

        <div class="nav-links">
            <a href="#how">How it works</a>
            <a href="#why">Why it's safe</a>
            <a href="#faq">FAQ</a>
            @if(config('services.dropbox.client_id'))<a href="{{ route('dropbox') }}">Dropbox</a>@endif
            <a href="{{ route('subscription.pricing') }}">Pricing</a>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            @auth
                <details class="acct">
                    <summary>
                        <span class="acct-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        {{ Str::of(Auth::user()->name)->explode(' ')->first() }}
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="acct-panel">
                        {{-- The email matters: it is the only way to tell which Google
                             account you are signed in as when you have more than one. --}}
                        <div class="acct-who">
                            <b>{{ Auth::user()->name }}</b>
                            <span>{{ Auth::user()->email }}</span>
                        </div>
                        <a href="{{ route('subscription.manage') }}">Dashboard</a>
                        <a href="{{ route('shares.index') }}">Share with a collaborator</a>
                        <a href="{{ route('subscription.pricing') }}">Plans and billing</a>
                        @if(Auth::user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}">Admin</a>
                        @endif
                        <form method="POST" action="{{ route('auth.disconnect') }}">
                            @csrf
                            {{-- Same action either way: it signs out. Only a Google account has a Drive to disconnect. --}}
                            <button type="submit">{{ Auth::user()->isDropboxOnly() ? 'Sign out' : 'Disconnect Google Drive' }}</button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('auth.google') }}" class="btn btn-primary" data-cta="nav">Sign In</a>
            @endauth

            {{-- Native disclosure widget, so the mobile menu needs no JS at all.
                 The previous version styled a .burger that was never in the markup,
                 which left phones with no navigation whatsoever. --}}
            <details class="menu">
                <summary aria-label="Menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </summary>
                <div class="menu-panel">
                    <a href="#how">How it works</a>
                    <a href="#why">Why it's safe</a>
                    <a href="#faq">FAQ</a>
                    @if(config('services.dropbox.client_id'))<a href="{{ route('dropbox') }}">Dropbox</a>@endif
                    <a href="{{ route('subscription.pricing') }}">Pricing</a>
                </div>
            </details>
        </div>
    </div>
</nav>
