<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - WetoDrive</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

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
        }

        /* Navigation */
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

        /* Main Content */
        .main-content {
            padding: 40px 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .terms-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 50px 40px;
        }

        .last-updated {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-style: italic;
        }

        .terms-section {
            margin-bottom: 40px;
        }

        .terms-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8rem;
            padding-bottom: 10px;
            border-bottom: 2px solid #e1e5e9;
        }

        .terms-section h3 {
            color: #333;
            margin-bottom: 15px;
            margin-top: 25px;
            font-size: 1.3rem;
        }

        .terms-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .terms-section ul, .terms-section ol {
            margin-left: 30px;
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .terms-section li {
            margin-bottom: 10px;
        }

        .highlight-box {
            background: #f8f9fa;
            border-left: 4px solid #4285f4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .highlight-box h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
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

        .footer-section a {
            color: #b0b0b0;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            transition: color 0.3s;
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

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .terms-content {
                padding: 30px 20px;
            }

            .nav-links {
                display: none;
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
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @else
                        <a href="{{ route('subscription.manage') }}">Dashboard</a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>📜 Terms of Service</h1>
            <p>Please read these terms carefully before using WetoDrive</p>
        </div>

        <div class="terms-content">
            <div class="last-updated">Effective Date: {{ date('F j, Y') }}</div>

            <div class="terms-section">
                <h2>1. Agreement to Terms</h2>
                <p>
                    By accessing or using WetoDrive ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you disagree with any part of these terms, then you may not access the Service.
                </p>
                <p>
                    These Terms apply to all visitors, users, and others who access or use the Service.
                </p>
            </div>

            <div class="terms-section">
                <h2>2. Description of Service</h2>
                <p>
                    WetoDrive provides a file transfer service that allows users to transfer files from WeTransfer directly to their Google Drive account. The Service includes:
                </p>
                <ul>
                    <li>File transfer from WeTransfer URLs to Google Drive</li>
                    <li>User account management</li>
                    <li>Subscription plans with varying transfer limits and file sizes</li>
                    <li>Payment processing for premium features</li>
                </ul>
            </div>

            <div class="terms-section">
                <h2>3. User Accounts</h2>

                <h3>Account Creation</h3>
                <p>To use our Service, you must:</p>
                <ul>
                    <li>Have a valid Google account</li>
                    <li>Be at least 13 years of age</li>
                    <li>Provide accurate and complete information</li>
                    <li>Maintain the security of your account</li>
                </ul>

                <h3>Account Responsibilities</h3>
                <p>You are responsible for:</p>
                <ul>
                    <li>All activities that occur under your account</li>
                    <li>Maintaining the confidentiality of your account</li>
                    <li>Notifying us immediately of any unauthorized use</li>
                </ul>
            </div>

            <div class="terms-section">
                <h2>4. Acceptable Use Policy</h2>

                <p>You agree NOT to use the Service to:</p>
                <ul>
                    <li>Transfer illegal, harmful, or offensive content</li>
                    <li>Violate any laws or regulations</li>
                    <li>Infringe on intellectual property rights</li>
                    <li>Transfer malware, viruses, or harmful code</li>
                    <li>Abuse, harass, or harm another person</li>
                    <li>Attempt to bypass subscription limits</li>
                    <li>Reverse engineer or attempt to extract source code</li>
                    <li>Use the Service for any illegal or unauthorized purpose</li>
                </ul>

                <div class="warning-box">
                    <h4>⚠️ Important Notice</h4>
                    <p>Violation of these terms may result in immediate termination of your account without refund.</p>
                </div>
            </div>

            <div class="terms-section">
                <h2>5. Subscription Plans</h2>

                <h3>Available Plans</h3>
                <p>We offer the following subscription tiers:</p>
                <ol>
                    <li><strong>Free Plan:</strong> 5 transfers/month, 100MB file size limit</li>
                    <li><strong>Pro Plan:</strong> 100 transfers/month, 2GB file size limit</li>
                    <li><strong>Premium Plan:</strong> Unlimited transfers, 5GB file size limit</li>
                </ol>

                <h3>Billing and Payments</h3>
                <ul>
                    <li>Subscriptions are billed monthly in advance</li>
                    <li>Prices may vary based on your location</li>
                    <li>All fees are non-refundable except as required by law</li>
                    <li>We reserve the right to change prices with 30 days notice</li>
                </ul>

                <h3>Cancellation</h3>
                <p>
                    You may cancel your subscription at any time. Upon cancellation, you will retain access to premium features until the end of your current billing period.
                </p>
            </div>

            <div class="terms-section">
                <h2>6. Intellectual Property</h2>

                <h3>Our Property</h3>
                <p>
                    The Service and its original content, features, and functionality are owned by WetoDrive and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.
                </p>

                <h3>Your Content</h3>
                <p>
                    You retain all rights to files you transfer through our Service. By using the Service, you grant us a limited license to process your files solely for the purpose of providing the Service.
                </p>
            </div>

            <div class="terms-section">
                <h2>7. Third-Party Services</h2>
                <p>Our Service integrates with third-party services including:</p>
                <ul>
                    <li><strong>Google Drive:</strong> For file storage</li>
                    <li><strong>WeTransfer:</strong> As the source of file transfers</li>
                    <li><strong>Payment Processors:</strong> Paystack and LemonSqueezy</li>
                </ul>
                <p>
                    Your use of these third-party services is subject to their respective terms of service and privacy policies.
                </p>
            </div>

            <div class="highlight-box">
                <h4>📋 Service Limitations</h4>
                <p>WetoDrive acts as an intermediary service. We are not responsible for:</p>
                <ul>
                    <li>The availability or functionality of WeTransfer or Google Drive</li>
                    <li>Files that expire on WeTransfer before transfer completion</li>
                    <li>Google Drive storage limitations on your account</li>
                </ul>
            </div>

            <div class="terms-section">
                <h2>8. Disclaimer of Warranties</h2>
                <p>
                    THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT.
                </p>
                <p>
                    We do not warrant that:
                </p>
                <ul>
                    <li>The Service will be uninterrupted or error-free</li>
                    <li>All file transfers will be successful</li>
                    <li>The Service will meet your specific requirements</li>
                    <li>Any errors in the Service will be corrected</li>
                </ul>
            </div>

            <div class="terms-section">
                <h2>9. Limitation of Liability</h2>
                <p>
                    TO THE MAXIMUM EXTENT PERMITTED BY LAW, WETODRIVE SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING WITHOUT LIMITATION, LOSS OF PROFITS, DATA, USE, GOODWILL, OR OTHER INTANGIBLE LOSSES.
                </p>
                <p>
                    Our total liability shall not exceed the amount you paid us in the twelve (12) months preceding the claim.
                </p>
            </div>

            <div class="terms-section">
                <h2>10. Indemnification</h2>
                <p>
                    You agree to defend, indemnify, and hold harmless WetoDrive and its affiliates, officers, directors, employees, and agents from any claims, damages, obligations, losses, liabilities, costs, or debt arising from:
                </p>
                <ul>
                    <li>Your use of and access to the Service</li>
                    <li>Your violation of these Terms</li>
                    <li>Your violation of any third party rights</li>
                    <li>Any content you transfer through the Service</li>
                </ul>
            </div>

            <div class="terms-section">
                <h2>11. Termination</h2>
                <p>
                    We may terminate or suspend your account immediately, without prior notice or liability, for any reason, including without limitation if you breach these Terms.
                </p>
                <p>
                    Upon termination, your right to use the Service will cease immediately. All provisions of these Terms which should reasonably survive termination shall survive.
                </p>
            </div>

            <div class="terms-section">
                <h2>12. Governing Law</h2>
                <p>
                    These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which WetoDrive operates, without regard to its conflict of law provisions.
                </p>
            </div>

            <div class="terms-section">
                <h2>13. Changes to Terms</h2>
                <p>
                    We reserve the right to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days notice prior to any new terms taking effect.
                </p>
                <p>
                    By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms.
                </p>
            </div>

            <div class="terms-section">
                <h2>14. Contact Information</h2>
                <p>If you have any questions about these Terms, please contact us:</p>
                <ul>
                    <li><strong>Email:</strong> michael@wetodrive.com</li>
                    <li><strong>Website:</strong> wetodrive.com</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>📦 WetoDrive</h4>
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('subscription.pricing') }}">Pricing</a>
                @auth
                    <a href="{{ route('subscription.manage') }}">Dashboard</a>
                @endauth
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
            <p>&copy; {{ date('Y') }} WetoDrive. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>