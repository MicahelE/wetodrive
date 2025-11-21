<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WetoDrive - Manage Subscription</title>
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
            padding: 40px 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .card h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .current-plan {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .plan-info h3 {
            color: #333;
            margin-bottom: 5px;
        }

        .plan-details {
            color: #666;
            font-size: 0.9rem;
        }

        .plan-status {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-expired {
            background: #fff3cd;
            color: #856404;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #4285f4;
            color: white;
        }

        .btn-primary:hover {
            background: #3367d6;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-outline {
            background: transparent;
            color: #4285f4;
            border: 2px solid #4285f4;
        }

        .btn-outline:hover {
            background: #4285f4;
            color: white;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table td {
            color: #555;
        }

        .table tr:hover {
            background: #f8f9fa;
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

        .usage-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .usage-stat {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .usage-stat .number {
            font-size: 2rem;
            font-weight: bold;
            color: #4285f4;
            margin-bottom: 5px;
        }

        .usage-stat .label {
            color: #666;
            font-size: 0.9rem;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: #4285f4;
            transition: width 0.3s ease;
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

        .producthunt-badge {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .producthunt-badge img {
                width: 200px !important;
                height: auto !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('home') }}" class="back-link">← Back to Home</a>

        <div class="header">
            <h1>📊 Manage Subscription</h1>
            <p style="opacity: 0.9;">{{ Auth::user()->name }}</p>
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

        <!-- Current Plan -->
        <div class="card">
            <h2>🎯 Current Plan</h2>

            @if($activeSubscription)
                <div class="current-plan">
                    <div class="plan-info">
                        <h3>{{ $activeSubscription->subscriptionPlan->name }} Plan</h3>
                        <div class="plan-details">
                            {{ $activeSubscription->getFormattedAmount() }}/month •
                            {{ $activeSubscription->subscriptionPlan->isUnlimitedTransfers() ? 'Unlimited' : $activeSubscription->subscriptionPlan->transfer_limit }} transfers •
                            {{ $activeSubscription->subscriptionPlan->getFormattedFileSize() }} files
                        </div>
                        <div class="plan-details">
                            Started: {{ $activeSubscription->started_at->format('M j, Y') }}
                            @if($activeSubscription->expires_at)
                                • Expires: {{ $activeSubscription->expires_at->format('M j, Y') }}
                            @endif
                        </div>
                    </div>
                    <span class="plan-status status-{{ $activeSubscription->status }}">
                        {{ ucfirst($activeSubscription->status) }}
                    </span>
                </div>

                <!-- Usage Stats -->
                <div class="usage-stats">
                    <div class="usage-stat">
                        <div class="number">{{ $activeSubscription->transfers_used }}</div>
                        <div class="label">Transfers Used</div>
                        @if(!$activeSubscription->subscriptionPlan->isUnlimitedTransfers())
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ ($activeSubscription->transfers_used / $activeSubscription->subscriptionPlan->transfer_limit) * 100 }}%"></div>
                            </div>
                        @endif
                    </div>
                    <div class="usage-stat">
                        <div class="number">{{ $activeSubscription->subscriptionPlan->isUnlimitedTransfers() ? '∞' : $activeSubscription->getRemainingTransfers() }}</div>
                        <div class="label">Remaining</div>
                    </div>
                    <div class="usage-stat">
                        <div class="number">{{ Auth::user()->total_transfers }}</div>
                        <div class="label">Total Transfers</div>
                    </div>
                </div>

                <div class="actions">
                    <a href="{{ route('subscription.pricing') }}" class="btn btn-primary">Upgrade Plan</a>
                    @if($activeSubscription->isActive())
                        <form method="POST" action="{{ route('subscription.cancel') }}" style="display: inline;"
                              onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                            @csrf
                            <button type="submit" class="btn btn-danger">Cancel Subscription</button>
                        </form>
                    @endif
                </div>
            @else
                <div class="current-plan">
                    <div class="plan-info">
                        <h3>Free Plan</h3>
                        <div class="plan-details">
                            5 transfers per month • 100MB files • Basic features
                        </div>
                    </div>
                    <span class="plan-status status-active">Active</span>
                </div>

                <div class="actions">
                    <a href="{{ route('subscription.pricing') }}" class="btn btn-primary">Upgrade to Pro</a>
                </div>
            @endif
        </div>

        <!-- Subscription History -->
        @if($subscriptionHistory->count() > 0)
            <div class="card">
                <h2>📋 Subscription History</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Provider</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscriptionHistory as $subscription)
                            <tr>
                                <td>{{ $subscription->subscriptionPlan->name }}</td>
                                <td>{{ ucfirst($subscription->payment_provider) }}</td>
                                <td>{{ $subscription->currency === 'NGN' ? '₦' : '$' }}{{ number_format($subscription->amount_paid, $subscription->currency === 'NGN' ? 0 : 2) }}</td>
                                <td>
                                    <span class="plan-status status-{{ $subscription->status }}">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $subscription->started_at->format('M j, Y') }}
                                    @if($subscription->expires_at)
                                        - {{ $subscription->expires_at->format('M j, Y') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Payment History -->
        @if($paymentHistory->count() > 0)
            <div class="card">
                <h2>💳 Payment History</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Provider</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentHistory as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('M j, Y') }}</td>
                                <td>{{ $transaction->userSubscription->subscriptionPlan->name ?? 'N/A' }}</td>
                                <td>{{ $transaction->getFormattedAmount() }}</td>
                                <td>{{ ucfirst($transaction->provider) }}</td>
                                <td>
                                    <span class="plan-status status-{{ $transaction->status === 'success' ? 'active' : ($transaction->status === 'failed' ? 'cancelled' : 'expired') }}">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div style="text-align: center; margin-top: 40px;">
            <a href="{{ route('subscription.pricing') }}" class="btn btn-outline">View All Plans</a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>📦 WetoDrive</h4>
                <p style="font-size: 0.9rem;">
                    Transfer files from WeTransfer to Google Drive instantly. No downloads, no storage limits on your device.
                </p>
                <div class="producthunt-badge">
                    <a href="https://www.producthunt.com/products/wetodrive?embed=true&utm_source=badge-featured&utm_medium=badge&utm_source=badge-wetodrive" target="_blank">
                        <img src="https://api.producthunt.com/widgets/embed-image/v1/featured.svg?post_id=1029974&theme=light&t=1761306053608" alt="WetoDrive - Automatically save WeTransfer files to Google Drive | Product Hunt" style="width: 250px; height: 54px;" width="250" height="54" />
                    </a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                <a href="{{ route('subscription.manage') }}">Dashboard</a>
            </div>
            <div class="footer-section">
                <h4>WeTransfer Guides</h4>
                <a href="{{ route('seo.pricing') }}">WeTransfer Pricing</a>
                <a href="{{ route('seo.send-files') }}">How to Send Files</a>
                <a href="{{ route('seo.upload') }}">Upload Tutorial</a>
                <a href="{{ route('seo.free') }}">Free Plan Guide</a>
                <a href="{{ route('seo.alternative') }}">WeTransfer Alternative</a>
                <a href="{{ route('seo.google-drive-guide') }}">Save to Google Drive</a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="{{ route('support.help') }}">Help Center</a>
                <a href="{{ route('support.contact') }}">Contact Us</a>
                <a href="{{ route('support.privacy') }}">Privacy Policy</a>
                <a href="{{ route('support.terms') }}">Terms of Service</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} WetoDrive. All rights reserved. Built with ❤️ for seamless file transfers.</p>
        </div>
    </footer>
</body>
</html>