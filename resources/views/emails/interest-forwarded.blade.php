<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Buyer Interest — Strongmas Residence</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 620px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #ffffff; padding: 32px 40px 24px; border-bottom: 2px solid #f0f0f0; text-align: center; }
        .header img { max-width: 280px; height: auto; }
        .body { padding: 36px 40px; }
        .body h1 { font-size: 22px; color: #111827; margin-bottom: 12px; }
        .body p { font-size: 14px; color: #374151; line-height: 1.6; margin-bottom: 14px; }
        .badge { display: inline-block; background: #fef9c3; border: 1px solid #fde68a; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: bold; color: #78350f; margin-bottom: 18px; }
        .detail-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 24px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; align-items: baseline; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; padding-bottom: 0; }
        .detail-label { color: #6b7280; }
        .detail-value { font-weight: bold; color: #111827; text-align: right; }
        .cta-wrap { text-align: center; margin: 28px 0; }
        .cta-btn { display: inline-block; background: #111827; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: bold; letter-spacing: 0.02em; }
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
        <h1>Hi {{ $rep->name }}, you have a new lead!</h1>
        <p>A buyer has expressed interest in a property and has been assigned to you. Please reach out to them at your earliest convenience.</p>

        <div class="badge">🏠 {{ $interest->property->development_name }}</div>

        <div class="detail-box">
            <div class="detail-row">
                <span class="detail-label">Buyer name</span>
                <span class="detail-value">{{ $interest->buyer->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value">
                    <a href="mailto:{{ $interest->buyer->email }}" style="color:#111827;">{{ $interest->buyer->email }}</a>
                </span>
            </div>
            @if($interest->buyer->phone)
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value">{{ $interest->buyer->phone }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Property</span>
                <span class="detail-value">{{ $interest->property->development_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Address</span>
                <span class="detail-value">{{ $interest->property->address }}</span>
            </div>
            @if($interest->unit)
            <div class="detail-row">
                <span class="detail-label">Unit interested in</span>
                <span class="detail-value">{{ $interest->unit->label }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">List price</span>
                <span class="detail-value">{{ naira($interest->unit->list_price_kobo) }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Interest registered</span>
                <span class="detail-value">{{ $interest->created_at->format('j F Y') }}</span>
            </div>
        </div>

        @if($interest->message)
        <p><strong>Buyer's note:</strong> "{{ $interest->message }}"</p>
        @endif

        <div class="cta-wrap">
            <a href="{{ config('app.url') }}/admin/interests" class="cta-btn">View in Admin Portal &rarr;</a>
        </div>

        <hr class="divider">

        <p style="font-size:13px;color:#6b7280;text-align:center;">
            You are receiving this email because you have been assigned as the sales representative for this lead by the Strongmas admin team.
        </p>
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
