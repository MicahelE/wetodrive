<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WetoDrive - Pricing Plans</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #2a42f7 0%, #1a2d99 100%);
            min-height: 100vh;
        }

        /* Header Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #4285f4;
        }

        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #4285f4;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
        }

        /* Mobile Menu Overlay */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background: white;
            z-index: 1002;
            padding: 20px;
            transition: right 0.3s ease;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .mobile-menu.active {
            right: 0;
        }

        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .mobile-menu-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .mobile-nav-links {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .mobile-nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: color 0.3s;
        }

        .mobile-nav-links a:hover {
            color: #4285f4;
        }

        /* Main Content */
        .main-content {
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
            color: white;
        }

        .header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }


        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .pricing-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }

        .pricing-card.popular {
            border: 3px solid #4285f4;
            transform: scale(1.05);
        }

        .popular-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #4285f4;
            color: white;
            padding: 8px 24px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .plan-name {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: bold;
            color: #4285f4;
            margin-bottom: 5px;
        }

        .plan-price .currency {
            font-size: 1.5rem;
            vertical-align: top;
        }

        .plan-period {
            color: #666;
            margin-bottom: 30px;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 40px;
            text-align: left;
        }

        .plan-features li {
            padding: 8px 0;
            color: #555;
            position: relative;
            padding-left: 25px;
        }

        .plan-features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }

        .plan-button {
            width: 100%;
            background: #4285f4;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .plan-button:hover {
            background: #3367d6;
        }

        .plan-button.free {
            background: #6c757d;
            cursor: not-allowed;
        }

        .plan-button.current {
            background: #28a745;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .payment-providers {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            align-items: center;
            color: white;
        }

        .provider-logo {
            height: 30px;
            filter: brightness(0) invert(1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="{{ route('home') }}" class="logo">
                📦 WetoDrive
            </a>

            <div class="nav-links">
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                @auth
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
                    @else
                        <a href="{{ route('subscription.manage') }}">Dashboard</a>
                    @endif
                @endauth
            </div>

            <div class="user-menu">
                @auth
                    <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                    <form method="POST" action="{{ route('auth.disconnect') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" style="background: none; border: none; color: #333; font-weight: 500; cursor: pointer;">Sign Out</button>
                    </form>
                @else
                    <a href="{{ route('auth.google') }}" class="btn" style="background: #4285f4; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 3px 8px rgba(66, 133, 244, 0.3);" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(66, 133, 244, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 8px rgba(66, 133, 244, 0.3)'">
                        Sign In
                    </a>
                @endauth
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="logo">WetoDrive</div>
            <button class="mobile-menu-close" onclick="closeMobileMenu()">×</button>
        </div>
        <div class="mobile-nav-links">
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('subscription.pricing') }}">Pricing</a>
            @auth
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
                @else
                    <a href="{{ route('subscription.manage') }}">Dashboard</a>
                @endif
                <form method="POST" action="{{ route('auth.disconnect') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" style="background: none; border: none; color: #333; font-weight: 500; padding: 15px 0; border-bottom: 1px solid #f0f0f0; width: 100%; text-align: left; cursor: pointer;">Sign Out</button>
                </form>
            @else
                <a href="{{ route('auth.google') }}">Sign In</a>
            @endauth
        </div>
    </div>

    <div class="main-content">
        <div class="container">

        <div class="header">
            <h1>📦 Choose Your Plan</h1>
            <p>Transfer files from WeTransfer to Google Drive with our flexible plans</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <div class="pricing-grid">
            @foreach($plans as $plan)
                <div class="pricing-card {{ $plan->slug === 'pro' ? 'popular' : '' }}">
                    @if($plan->slug === 'pro')
                        <div class="popular-badge">Most Popular</div>
                    @endif

                    <div class="plan-name">{{ $plan->name }}</div>

                    <div class="plan-price">
                        @if($plan->price_ngn == 0 && $plan->price_usd == 0)
                            <span class="currency">Free</span>
                        @else
                            <span class="currency">{{ $userCountry === 'NG' ? '₦' : '$' }}</span>{{ number_format($plan->getPriceForCountry($userCountry), 0) }}
                        @endif
                    </div>

                    @if($plan->price_ngn > 0 || $plan->price_usd > 0)
                        <div class="plan-period">per month</div>
                    @else
                        <div class="plan-period">forever</div>
                    @endif

                    <ul class="plan-features">
                        @foreach($plan->features as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>

                    @auth
                        @if($plan->slug === 'free')
                            @if(Auth::user()->subscription_tier === 'free')
                                <button class="plan-button current">Current Plan</button>
                            @else
                                <button class="plan-button free">Downgrade Available</button>
                            @endif
                        @else
                            @if(Auth::user()->subscription_tier === $plan->slug)
                                <button class="plan-button current">Current Plan</button>
                            @else
                                <form method="POST" action="{{ route('subscription.subscribe') }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <button type="submit" class="plan-button">
                                        @if(Auth::user()->subscription_tier === 'free')
                                            Get Started
                                        @else
                                            Upgrade
                                        @endif
                                    </button>
                                </form>
                            @endif
                        @endif
                    @else
                        @if($plan->slug === 'free')
                            <a href="{{ route('auth.google') }}" class="plan-button">Sign Up Free</a>
                        @else
                            <a href="{{ route('auth.google') }}" class="plan-button">Get Started</a>
                        @endif
                    @endauth
                </div>
            @endforeach
        </div>

        @auth
            <div style="text-align: center;">
                <a href="{{ route('subscription.manage') }}" style="color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 8px;">
                    Manage Your Subscription
                </a>
            </div>
        @endauth

        <div class="payment-providers">
            <span>Secure payments powered by:</span>
            @if($paymentProvider === 'paystack')
                <span>Paystack</span>
            @else
                <span>LemonSqueezy</span>
            @endif
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const overlay = document.getElementById('mobileMenuOverlay');
            const menu = document.getElementById('mobileMenu');

            overlay.classList.add('active');
            menu.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            const overlay = document.getElementById('mobileMenuOverlay');
            const menu = document.getElementById('mobileMenu');

            overlay.classList.remove('active');
            menu.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close menu when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMobileMenu();
            }
        });

        // Debug location detection
        console.log('Location Detection Debug:', {
            userCountry: '{{ $userCountry }}',
            paymentProvider: '{{ $paymentProvider }}',
            countryName: '{{ $userCountry === 'NG' ? 'Nigeria' : 'International' }}',
            paymentService: '{{ $paymentProvider === 'paystack' ? 'Paystack' : 'LemonSqueezy' }}'
        });
    </script>
</body>
</html>