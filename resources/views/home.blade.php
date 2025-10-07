<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WetoDrive - WeTransfer to Google Drive</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 90%;
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
            background: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .submit-button:hover {
            background: #218838;
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
        
        .user-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .user-info p {
            margin: 0;
            color: #2d5a2d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 WetoDrive</h1>
            <p>Transfer files from WeTransfer to Google Drive</p>
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

        @auth
            <div class="user-info" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p><strong>Connected as:</strong> {{ Auth::user()->name }} ({{ Auth::user()->email }})</p>
                    <p style="margin-top: 5px; font-size: 0.9rem;">
                        <strong>Plan:</strong> {{ ucfirst(Auth::user()->subscription_tier) }}
                        @if(Auth::user()->hasActiveSubscription())
                            @php
                                $subscription = Auth::user()->activeSubscription;
                            @endphp
                            • {{ $subscription->getRemainingTransfers() === null ? 'Unlimited' : $subscription->getRemainingTransfers() }} transfers remaining
                        @else
                            • Free plan (5 transfers/month)
                        @endif
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('subscription.pricing') }}" style="background: #4285f4; color: white; border: none; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9rem;">
                        @if(Auth::user()->subscription_tier === 'free')
                            Upgrade
                        @else
                            Manage
                        @endif
                    </a>
                    <form method="POST" action="{{ route('auth.disconnect') }}" style="display: inline;">
                        @csrf
                        <button type="submit" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">
                            Disconnect
                        </button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('transfer') }}" class="transfer-form">
                @csrf
                <div class="form-group">
                    <label for="wetransfer_url">WeTransfer URL</label>
                    <input 
                        type="url" 
                        id="wetransfer_url" 
                        name="wetransfer_url" 
                        placeholder="https://wetransfer.com/downloads/..." 
                        required
                        value="{{ old('wetransfer_url') }}"
                    >
                </div>
                <button type="submit" class="submit-button">
                    🚀 Transfer to Google Drive
                </button>
            </form>
        @else
            <div class="auth-section">
                <p style="margin-bottom: 15px;">Connect your Google Drive to get started</p>
                <a href="{{ route('auth.google') }}" class="auth-button">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Connect Google Drive
                </a>
            </div>
        @endauth
    </div>
</body>
</html>