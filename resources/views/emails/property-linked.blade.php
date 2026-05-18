<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Property Linked — Strongmas Residence</title>
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
        .detail-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 24px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; align-items: baseline; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; padding-bottom: 0; }
        .detail-label { color: #6b7280; }
        .detail-value { font-weight: bold; color: #111827; text-align: right; }
        .cta-wrap { text-align: center; margin: 28px 0; }
        .cta-btn { display: inline-block; background: #111827; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: bold; letter-spacing: 0.02em; }
        .contacts { font-size: 13px; color: #374151; }
        .contacts li { margin-bottom: 6px; list-style: none; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 40px; text-align: center; }
        .footer img { height: 36px; width: auto; margin-bottom: 10px; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <img src="{{ $message->embed(public_path('logo1.png')) }}" alt="Strongmas Residence">
    </div>

    <div class="body">
        <h1>New property linked, {{ explode(' ', $buyer->name)[0] }}!</h1>
        <p>A property has been linked to your Strongmas Residence account. You can now track your payment schedule and balance directly from your subscriber's portal.</p>

        <div class="property-badge">🏠 {{ $ownership->display_name }}</div>

        <div class="detail-box">
            <div class="detail-row">
                <span class="detail-label">Agreed price</span>
                <span class="detail-value">{{ naira($ownership->agreed_price_kobo) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment plan</span>
                <span class="detail-value">
                    {{ $ownership->payment_plan === 'instalment'
                        ? $ownership->total_instalments . ' instalments'
                        : 'Outright payment' }}
                </span>
            </div>
            @if($ownership->start_date)
            <div class="detail-row">
                <span class="detail-label">Start date</span>
                <span class="detail-value">{{ $ownership->start_date->format('j F Y') }}</span>
            </div>
            @endif
            @if($ownership->expected_completion_date)
            <div class="detail-row">
                <span class="detail-label">Expected completion</span>
                <span class="detail-value">{{ $ownership->expected_completion_date->format('j F Y') }}</span>
            </div>
            @endif
        </div>

        <div class="cta-wrap">
            <a href="{{ config('app.url') }}" class="cta-btn">View Your Portal &rarr;</a>
        </div>

        <p style="text-align:center; font-size:12px; color:#9ca3af;">
            <a href="{{ config('app.url') }}" style="color:#6b7280;">{{ config('app.url') }}</a>
        </p>

        <hr class="divider">

        <p><strong>Need help?</strong> Our support team is always ready:</p>
        <ul class="contacts">
            <li>📞 Call &nbsp;· &nbsp;0700-STRONGMAS (0901‑077‑77‑77)</li>
            <li>📧 Email · <a href="mailto:hello@strongmas.ng" style="color:#111827;">hello@strongmas.ng</a></li>
            <li>💬 WhatsApp · <a href="https://wa.me/2349010777777" style="color:#111827;">0901 077 7777</a></li>
        </ul>
    </div>

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
