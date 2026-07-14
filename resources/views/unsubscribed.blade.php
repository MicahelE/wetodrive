<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Unsubscribed | {{ config('app.name') }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f6f8fb;
            color: #1f2933;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .card {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, .08);
            max-width: 460px;
            text-align: center;
        }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { line-height: 1.6; color: #52606d; margin: 0 0 12px; }
        a.button {
            display: inline-block;
            margin-top: 12px;
            background: #4285f4;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>You're unsubscribed</h1>
        <p>We won't send <strong>{{ $user->email }}</strong> any more marketing email.</p>
        <p>You'll still get essential messages about transfers you start and any payments you make.</p>
        <a class="button" href="{{ route('home') }}">Back to {{ config('app.name') }}</a>
    </div>
</body>
</html>
