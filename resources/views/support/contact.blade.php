<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - WetoDrive</title>
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
            max-width: 800px;
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

        .contact-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 50px 40px;
            text-align: center;
        }

        .contact-icon {
            font-size: 4rem;
            margin-bottom: 30px;
        }

        .contact-info {
            margin-bottom: 40px;
        }

        .contact-info h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2rem;
        }

        .contact-info p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .email-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
        }

        .email-address {
            font-size: 1.5rem;
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .email-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #4285f4;
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
        }

        .email-button:hover {
            background: #3367d6;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
        }

        .contact-tips {
            margin-top: 40px;
            padding: 30px;
            background: #e8f5e8;
            border-radius: 12px;
        }

        .contact-tips h3 {
            color: #2d5a2d;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .contact-tips ul {
            text-align: left;
            color: #2d5a2d;
            list-style-position: inside;
            margin-left: 0;
        }

        .contact-tips li {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .quick-link {
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .quick-link:hover {
            border-color: #4285f4;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .quick-link-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .quick-link h4 {
            margin-bottom: 5px;
            color: #333;
        }

        .quick-link p {
            font-size: 0.9rem;
            color: #666;
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

            .email-address {
                font-size: 1.2rem;
            }

            .nav-links {
                display: none;
            }

            .quick-links {
                grid-template-columns: 1fr;
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
            <h1>📧 Contact Us</h1>
            <p>We're here to help with any questions or issues</p>
        </div>

        <div class="contact-content">
            <div class="contact-icon">💬</div>

            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>Have a question, feedback, or need assistance? We'd love to hear from you!</p>
                <p>Our support team is available to help you with any issues related to WetoDrive.</p>
            </div>

            <div class="email-section">
                <div class="email-address">michael@wetodrive.com</div>
                <a href="mailto:michael@wetodrive.com?subject=WetoDrive%20Support%20Request" class="email-button">
                    ✉️ Send Email
                </a>
            </div>

            <div class="contact-tips">
                <h3>📝 When Contacting Support</h3>
                <ul>
                    <li>Include your registered email address</li>
                    <li>Describe the issue you're experiencing in detail</li>
                    <li>Provide the WeTransfer URL if transfer-related</li>
                    <li>Mention any error messages you received</li>
                    <li>Include your subscription plan (Free, Pro, or Premium)</li>
                </ul>
            </div>

            <div class="quick-links">
                <a href="{{ route('support.help') }}" class="quick-link">
                    <div class="quick-link-icon">❓</div>
                    <h4>Help Center</h4>
                    <p>Browse FAQs and guides</p>
                </a>

                <a href="{{ route('subscription.pricing') }}" class="quick-link">
                    <div class="quick-link-icon">💎</div>
                    <h4>Pricing Plans</h4>
                    <p>View subscription options</p>
                </a>

                @auth
                <a href="{{ route('subscription.manage') }}" class="quick-link">
                    <div class="quick-link-icon">📊</div>
                    <h4>Dashboard</h4>
                    <p>Manage your account</p>
                </a>
                @else
                <a href="{{ route('auth.google') }}" class="quick-link">
                    <div class="quick-link-icon">🚀</div>
                    <h4>Get Started</h4>
                    <p>Sign in with Google</p>
                </a>
                @endauth
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