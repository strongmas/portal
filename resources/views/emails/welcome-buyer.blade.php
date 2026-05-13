<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to Strongmas Residence Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 620px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #ffffff; padding: 32px 40px 24px; border-bottom: 2px solid #f0f0f0; text-align: center; }
        .header img { max-width: 280px; height: auto; }
        .body { padding: 36px 40px; }
        .body h1 { font-size: 22px; color: #111827; margin-bottom: 12px; }
        .body p { font-size: 14px; color: #374151; line-height: 1.6; margin-bottom: 14px; }
        .property-badge { display: inline-block; background: #fef9c3; border: 1px solid #fde68a; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: bold; color: #78350f; margin-bottom: 18px; }
        .credentials { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 24px; margin: 20px 0; }
        .credentials p { margin-bottom: 10px; font-size: 14px; }
        .credentials p:last-child { margin-bottom: 0; }
        .credentials .label { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.07em; color: #6b7280; margin-bottom: 2px; }
        .credentials .value { font-size: 16px; font-weight: bold; color: #111827; }
        .credentials .password-box { display: inline-block; background: #e5e7eb; border-radius: 6px; padding: 4px 12px; font-family: 'Courier New', monospace; font-size: 18px; letter-spacing: 0.05em; color: #1f2937; margin-top: 4px; }
        .cta-wrap { text-align: center; margin: 28px 0; }
        .cta-btn { display: inline-block; background: #111827; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: bold; letter-spacing: 0.02em; }
        .warning { background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 4px; padding: 12px 16px; margin: 20px 0; font-size: 13px; color: #b91c1c; }
        .contacts { font-size: 13px; color: #374151; }
        .contacts li { margin-bottom: 6px; list-style: none; padding-left: 0; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 40px; text-align: center; }
        .footer img { height: 36px; width: auto; margin-bottom: 10px; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header: wide logo --}}
    <div class="header">
        <img src="{{ $message->embed(public_path('logo1.png')) }}" alt="Strongmas Residence">
    </div>

    <div class="body">
        <h1>Welcome, {{ $buyer->name }}!</h1>
        <p>Your Strongmas Residence subscriber's portal account has been set up. You can now log in to track your property journey, view your payment schedule, and communicate with our team.</p>

        @if($propertyName)
            <div class="property-badge">🏠 {{ $propertyName }}</div>
        @endif

        {{-- Login credentials --}}
        <div class="credentials">
            <p>
                <span class="label">Login Email</span>
                <span class="value">{{ $buyer->email }}</span>
            </p>
            <p>
                <span class="label">Temporary Password</span><br>
                <span class="password-box">{{ $tempPassword }}</span>
            </p>
        </div>

        <div class="warning">
            <strong>Important:</strong> Please change your password immediately after your first login to keep your account secure.
        </div>

        <div class="cta-wrap">
            <a href="{{ config('app.url') }}" class="cta-btn">Access Your Portal &rarr;</a>
        </div>

        <p style="text-align:center; font-size:12px; color:#9ca3af;">
            <a href="{{ config('app.url') }}" style="color:#6b7280;">{{ config('app.url') }}</a>
        </p>

        <hr class="divider">

        <p><strong>Need help?</strong> Our support team is always ready:</p>
        <ul class="contacts">
            <li>📞 Call &nbsp;· &nbsp;0700-STRONGMAS (0700‑786‑766‑627)</li>
            <li>📧 Email · <a href="mailto:hello@strongmas.ng" style="color:#111827;">hello@strongmas.ng</a></li>
            <li>💬 WhatsApp · <a href="https://wa.me/2348034441209" style="color:#111827;">0803 444 1209</a></li>
        </ul>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <img src="{{ $message->embed(public_path('logo.png')) }}" alt="Strongmas"><br>
        <p>
            &copy; {{ date('Y') }} Strongmas Residence. All rights reserved.<br>
            Lagos, Nigeria &nbsp;·&nbsp; <a href="mailto:hello@strongmas.ng" style="color:#9ca3af;">hello@strongmas.ng</a>
        </p>
    </div>

</div>
</body>
</html>
