<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WetoDrive - WeTransfer to Google Drive</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#4285f4">
    
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-174D73GPWB"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-174D73GPWB');
        
        // Track file transfer events
        function trackFileTransfer(transferUrl) {
            gtag('event', 'file_transfer_started', {
                'event_category': 'engagement',
                'event_label': 'WeTransfer to Google Drive',
                'value': 1
            });
        }
        
        // Track subscription events
        function trackSubscription(planName, price) {
            gtag('event', 'subscription_selected', {
                'event_category': 'ecommerce',
                'event_label': planName,
                'value': price
            });
        }
    </script>
    
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .auth-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            text-align: center;
        }
        
        .auth-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #4285f4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .auth-button:hover {
            background: #3367d6;
        }
        
        .transfer-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4285f4;
        }
        
        .submit-button {
            width: 100%;
            background: #4285f4;
            color: white;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
        }

        .submit-button:hover {
            background: #3367d6;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
        }
        
        .submit-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 12px;
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
        
        .alert a {
            color: #155724;
            text-decoration: underline;
            font-weight: 600;
        }
        
        .alert a:hover {
            color: #0c3e1a;
            text-decoration: none;
        }
        
        .user-info {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .user-info p {
            margin: 0;
            color: #2d5a2d;
        }

        /* Quick Action Cards */
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-2px);
            border-color: #4285f4;
        }

        .action-card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .action-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .action-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .action-card .btn {
            background: #4285f4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
        }

        .action-card .btn:hover {
            background: #3367d6;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
        }

        .action-card .btn.btn-secondary {
            background: #6c757d;
        }

        .action-card .btn.btn-secondary:hover {
            background: #5a6268;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 40px 20px 20px;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .footer-section h4 {
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: #fff;
        }

        .footer-section p {
            color: #b0b0b0;
            line-height: 1.6;
        }

        .footer-section a {
            color: #b0b0b0;
            text-decoration: none;
            transition: color 0.3s;
            display: block;
            margin-bottom: 8px;
        }

        .footer-section a:hover {
            color: #4285f4;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #808080;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .container {
                margin: 0 20px;
                max-width: none;
            }

            .action-cards {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
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
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                @auth
                    <a href="{{ route('subscription.manage') }}">Dashboard</a>
                @endauth
            </div>

            <div class="user-menu">
                @auth
                    <div class="user-avatar">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <span style="color: #333; font-weight: 500;">{{ Auth::user()->name }}</span>
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
            @auth
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
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
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                <a href="{{ route('auth.google') }}">Sign In</a>
            @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        @guest
            <!-- Welcome Section for Guest Users -->
            <div style="text-align: center; color: white; margin-bottom: 40px;">
                <h1 style="font-size: 3rem; margin-bottom: 15px;">📦 WetoDrive</h1>
                <p style="font-size: 1.3rem; opacity: 0.9; margin-bottom: 30px;">
                    Transfer files from WeTransfer to Google Drive instantly
                </p>
                <a href="{{ route('auth.google') }}" style="background: #4285f4; color: white; padding: 18px 32px; border-radius: 14px; text-decoration: none; font-size: 1.1rem; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(66, 133, 244, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(66, 133, 244, 0.3)'">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Get Started with Google Drive
                </a>
            </div>

            <!-- Feature Cards -->
            <div class="action-cards">
                <div class="action-card">
                    <div class="action-card-icon">🚀</div>
                    <h3>Instant Transfer</h3>
                    <p>No more manual downloading and uploading. Transfer files directly from WeTransfer to Google Drive.</p>
                </div>
                <div class="action-card">
                    <div class="action-card-icon">💾</div>
                    <h3>Save Storage</h3>
                    <p>Files stream directly to your Google Drive without taking up space on your device.</p>
                </div>
                <div class="action-card">
                    <div class="action-card-icon">⚡</div>
                    <h3>Fast & Secure</h3>
                    <p>Powered by Google's secure infrastructure with enterprise-grade encryption.</p>
                </div>
            </div>
        @endguest

        @auth
            <!-- User Dashboard Section -->
            <div class="container">
                @if(session('success'))
                    <div class="alert alert-success">
                        {!! session('success') !!}
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

                <div class="user-info">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <h3 style="margin: 0; color: #2d5a2d; font-size: 1.3rem;">Welcome back, {{ Auth::user()->name }}!</h3>
                            <p style="margin: 5px 0; opacity: 0.8;">{{ Auth::user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('auth.disconnect') }}" style="display: inline;">
                            @csrf
                            <button type="submit" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">
                                Disconnect
                            </button>
                        </form>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="background: rgba(255,255,255,0.5); padding: 15px; border-radius: 8px;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #2d5a2d;">{{ ucfirst(Auth::user()->subscription_tier) }}</div>
                            <div style="opacity: 0.8; font-size: 0.9rem;">Current Plan</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.5); padding: 15px; border-radius: 8px;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #2d5a2d;">
                                @if(Auth::user()->hasActiveSubscription())
                                    @php $subscription = Auth::user()->activeSubscription; @endphp
                                    {{ $subscription->getRemainingTransfers() === null ? '∞' : $subscription->getRemainingTransfers() }}
                                @else
                                    {{ 5 - Auth::user()->total_transfers }}
                                @endif
                            </div>
                            <div style="opacity: 0.8; font-size: 0.9rem;">Transfers Remaining</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.5); padding: 15px; border-radius: 8px;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #2d5a2d;">{{ Auth::user()->total_transfers }}</div>
                            <div style="opacity: 0.8; font-size: 0.9rem;">Total Transfers</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions for Authenticated Users -->
                <div class="action-cards">
                    <div class="action-card">
                        <div class="action-card-icon">🚀</div>
                        <h3>Transfer Files</h3>
                        <p>Paste your WeTransfer URL below to start the transfer process.</p>
                    </div>
                    <div class="action-card">
                        <div class="action-card-icon">📊</div>
                        <h3>Manage Subscription</h3>
                        <p>View your usage, payment history, and manage your subscription plan.</p>
                        <a href="{{ route('subscription.manage') }}" class="btn">View Dashboard</a>
                    </div>
                    <div class="action-card">
                        <div class="action-card-icon">⭐</div>
                        <h3>
                            @if(Auth::user()->subscription_tier === 'free')
                                Upgrade Plan
                            @else
                                View Plans
                            @endif
                        </h3>
                        <p>
                            @if(Auth::user()->subscription_tier === 'free')
                                Get more transfers and larger file sizes with our Pro and Premium plans.
                            @else
                                Explore all available plans and compare features.
                            @endif
                        </p>
                        <a href="{{ route('subscription.pricing') }}" class="btn {{ Auth::user()->subscription_tier === 'free' ? '' : 'btn-secondary' }}">
                            @if(Auth::user()->subscription_tier === 'free')
                                Upgrade Now
                            @else
                                View Plans
                            @endif
                        </a>
                    </div>
                </div>

                <!-- File Transfer Form -->
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 8px 20px rgba(0,0,0,0.1);">
                    <h2 style="margin-bottom: 20px; color: #333;">🔗 Transfer WeTransfer Files</h2>
                    <form method="POST" action="{{ route('transfer') }}" class="transfer-form" onsubmit="trackFileTransfer(this.wetransfer_url.value)">
                        @csrf
                        <div class="form-group">
                            <label for="wetransfer_url">WeTransfer URL</label>
                            <input
                                type="url"
                                id="wetransfer_url"
                                name="wetransfer_url"
                                placeholder="https://wetransfer.com/downloads/... or https://we.tl/t-..."
                                required
                                value="{{ old('wetransfer_url') }}"
                                style="font-size: 1rem; padding: 15px;"
                            >
                        </div>
                        <button type="submit" class="submit-button" style="font-size: 1.1rem; padding: 15px;">
                            🚀 Transfer to Google Drive
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>📦 WetoDrive</h4>
                <p style="font-size: 0.9rem;">
                    Transfer files from WeTransfer to Google Drive instantly. No downloads, no storage limits on your device.
                </p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                @auth
                    <a href="{{ route('subscription.manage') }}">Dashboard</a>
                @else
                    <a href="{{ route('auth.google') }}">Sign In</a>
                @endauth
            </div>
            <div class="footer-section">
                <h4>Features</h4>
                <a href="#" style="pointer-events: none;">Instant Transfers</a>
                <a href="#" style="pointer-events: none;">No Storage Used</a>
                <a href="#" style="pointer-events: none;">Secure & Fast</a>
                <a href="#" style="pointer-events: none;">Multiple File Formats</a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="#" style="pointer-events: none;">Help Center</a>
                <a href="#" style="pointer-events: none;">Contact Us</a>
                <a href="#" style="pointer-events: none;">Privacy Policy</a>
                <a href="#" style="pointer-events: none;">Terms of Service</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} WetoDrive. All rights reserved. Built with ❤️ for seamless file transfers.</p>
        </div>
    </footer>

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
    </script>
</body>
</html>