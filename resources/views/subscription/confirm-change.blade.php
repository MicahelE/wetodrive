<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WetoDrive - Confirm Plan Change</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#4285f4">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #2a42f7 0%, #1a2d99 100%);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        h1 { font-size: 1.6rem; color: #1a1a1a; margin-bottom: 20px; }

        .charge {
            background: #f6f8ff;
            border: 1px solid #dde3ff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .charge-amount { font-size: 2rem; font-weight: 700; color: #2a42f7; margin-bottom: 6px; }
        .charge-note { color: #555; font-size: 0.95rem; line-height: 1.5; }

        .details { list-style: none; margin-bottom: 28px; }
        .details li { padding: 10px 0; border-bottom: 1px solid #eee; color: #333; display: flex; justify-content: space-between; gap: 16px; }
        .details li:last-child { border-bottom: none; }
        .details .label { color: #666; }
        .details .value { font-weight: 600; }

        .actions { display: flex; gap: 12px; }

        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary { background: #2a42f7; color: white; }
        .btn-primary:hover { background: #1a2d99; }
        .btn-secondary { background: #f0f0f0; color: #555; }
        .btn-secondary:hover { background: #e2e2e2; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Switch from {{ $currentPlan->name }} to {{ $newPlan->name }}</h1>

        <div class="charge">
            @if($isUpgrade)
                <div class="charge-amount">About ${{ number_format($estimate, 2) }} today</div>
                <div class="charge-note">
                    That's the difference for the rest of your current billing period, since you've
                    already paid for {{ $currentPlan->name }}. After that it's
                    ${{ number_format($newPlan->price_usd, 0) }} per month, starting
                    {{ $subscription->expires_at?->format('M j, Y') }}.
                </div>
            @else
                <div class="charge-amount">Nothing today</div>
                <div class="charge-note">
                    You keep {{ $currentPlan->name }} until
                    {{ $subscription->expires_at?->format('M j, Y') }}. {{ $newPlan->name }} starts
                    after that at ${{ number_format($newPlan->price_usd, 0) }} per month.
                </div>
            @endif
        </div>

        <ul class="details">
            <li>
                <span class="label">File size limit</span>
                <span class="value">{{ $currentPlan->getFormattedFileSize() }} to {{ $newPlan->getFormattedFileSize() }}</span>
            </li>
            <li>
                <span class="label">Transfers</span>
                <span class="value">
                    {{ $newPlan->isUnlimitedTransfers() ? 'Unlimited' : $newPlan->transfer_limit . ' per month' }}
                </span>
            </li>
            @if($subscription->isSetToCancel())
                <li>
                    <span class="label">Your cancellation</span>
                    <span class="value">This restarts your subscription, so it won't end on {{ $subscription->expires_at?->format('M j, Y') }}.</span>
                </li>
            @endif
        </ul>

        <form method="POST" action="{{ route('subscription.apply-change') }}">
            @csrf
            <input type="hidden" name="plan_id" value="{{ $newPlan->id }}">
            <div class="actions">
                <a href="{{ route('subscription.pricing') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    {{ $isUpgrade ? 'Confirm upgrade' : 'Confirm change' }}
                </button>
            </div>
        </form>
    </div>
</body>
</html>
