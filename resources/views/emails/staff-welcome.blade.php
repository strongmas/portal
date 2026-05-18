<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Strongmas Admin Access</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 620px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #111827; padding: 28px 40px; text-align: center; }
        .header img { max-width: 160px; height: auto; }
        .body { padding: 36px 40px; }
        .body h1 { font-size: 20px; color: #111827; margin-bottom: 12px; }
        .body p { font-size: 14px; color: #374151; line-height: 1.6; margin-bottom: 14px; }
        .credentials { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 24px; margin: 20px 0; }
        .credentials p { margin-bottom: 10px; font-size: 14px; }
        .credentials p:last-child { margin-bottom: 0; }
        .label { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.07em; color: #6b7280; display: block; margin-bottom: 2px; }
        .value { font-size: 15px; font-weight: bold; color: #111827; }
        .password-box { display: inline-block; background: #e5e7eb; border-radius: 6px; padding: 4px 12px; font-family: 'Courier New', monospace; font-size: 17px; letter-spacing: 0.05em; color: #1f2937; margin-top: 4px; }
        .cta-wrap { text-align: center; margin: 28px 0; }
        .cta-btn { display: inline-block; background: #111827; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: bold; }
        .warning { background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 4px; padding: 12px 16px; margin: 20px 0; font-size: 13px; color: #b91c1c; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 40px; text-align: center; font-size: 12px; color: #9ca3af; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <img src="{{ $message->embed(public_path('logo1.png')) }}" alt="Strongmas">
    </div>

    <div class="body">
        <h1>{{ $isReset ? 'Your password has been reset' : 'Welcome to Strongmas Admin' }}, {{ $staff->name }}!</h1>

        @if($isReset)
            <p>Your admin account password has been reset by a super administrator. Use the credentials below to log in.</p>
        @else
            <p>A Strongmas admin account has been created for you. You can access the admin panel using the credentials below.</p>
            @if($staff->adminRole)
                <p>Your role: <strong>{{ $staff->adminRole->name }}</strong></p>
            @endif
        @endif

        <div class="credentials">
            <p>
                <span class="label">Login Email</span>
                <span class="value">{{ $staff->email }}</span>
            </p>
            <p>
                <span class="label">{{ $isReset ? 'New Password' : 'Temporary Password' }}</span><br>
                <span class="password-box">{{ $password }}</span>
            </p>
        </div>

        <div class="warning">
            <strong>Important:</strong> Change your password immediately after logging in.
        </div>

        <div class="cta-wrap">
            <a href="{{ config('app.url') }}/admin" class="cta-btn">Go to Admin Panel &rarr;</a>
        </div>

        <p style="font-size:13px;color:#6b7280;">
            Need help? Contact the super administrator.
        </p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} Strongmas Residence. All rights reserved.
    </div>
</div>
</body>
</html>
