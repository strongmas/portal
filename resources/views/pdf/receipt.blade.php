@php
    // DejaVu Sans (DomPDF default) lacks the ₦ glyph — use NGN text instead
    $money = fn(int $kobo) => 'NGN ' . number_format($kobo / 100, 0, '.', ',');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1a1814; background: #faf8f3; font-size: 13px; line-height: 1.5; }

        .page { padding: 40px 48px; max-width: 600px; margin: 0 auto; background: #faf8f3; }

        .header { border-bottom: 2px solid #1a1814; padding-bottom: 20px; margin-bottom: 24px; }
        .logo-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
        .logo-box { width: 32px; height: 32px; background: #ffe066; border: 1.5px solid #1a1814; border-radius: 5px; display: inline-flex; align-items: center; justify-content: center; font-weight: 900; font-size: 16px; }
        .brand { font-size: 18px; font-weight: 700; letter-spacing: -0.02em; }
        .receipt-no { font-family: monospace; font-size: 11px; color: #7a7469; letter-spacing: 0.05em; }

        .verified-badge { display: inline-block; background: #ffe066; border: 1.5px solid #1a1814; border-radius: 20px; padding: 2px 10px; font-size: 10px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; }

        .section-label { font-family: monospace; font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em; color: #7a7469; margin-bottom: 10px; }

        .row { display: flex; justify-content: space-between; align-items: baseline; padding: 6px 0; border-bottom: 1px dashed #d4cfc6; }
        .row:last-child { border-bottom: none; }
        .row-label { color: #7a7469; }
        .row-value { font-weight: 600; text-align: right; }
        .row-mono { font-family: monospace; font-size: 11px; text-align: right; }

        .amount-block { margin: 24px 0; padding: 20px 24px; border: 2px solid #1a1814; border-radius: 8px; background: #fff4b8; }
        .amount-label { font-family: monospace; font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em; color: #7a7469; }
        .amount-value { font-size: 32px; font-weight: 900; letter-spacing: -0.02em; }

        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px dashed #d4cfc6; display: flex; align-items: flex-start; justify-content: space-between; }
        .qr-box { width: 56px; height: 56px; border: 1.5px solid #1a1814; border-radius: 4px; background: repeating-linear-gradient(45deg, #1a1814 0 2px, #faf8f3 2px 5px); flex-shrink: 0; }
        .footer-note { font-size: 10px; color: #7a7469; max-width: 380px; line-height: 1.6; }
        .footer-note strong { color: #1a1814; }

        .separator { border: none; border-top: 1px dashed #d4cfc6; margin: 20px 0; }

        .punch-holes { display: flex; justify-content: space-between; margin-bottom: 24px; }
        .punch { width: 18px; height: 18px; border-radius: 50%; background: white; border: 1.5px dashed #d4cfc6; }
    </style>
</head>
<body>
<div class="page">

    {{-- Punch holes at top --}}
    <div class="punch-holes">
        <div class="punch"></div>
        <div class="punch"></div>
    </div>

    {{-- Header --}}
    <div class="header">
        <div class="logo-row">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="logo-box">S</div>
                <span class="brand">Strongmas</span>
            </div>
            <span class="receipt-no">{{ $payment->receipt_number }}</span>
        </div>
        <div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;">
            <span style="color:#7a7469;font-size:11px;">Official Payment Receipt</span>
            <span class="verified-badge">Verified</span>
        </div>
    </div>

    {{-- Property + buyer details --}}
    <div class="section-label">Transaction details</div>
    <div style="margin-bottom:24px;">
        <div class="row">
            <span class="row-label">Property</span>
            <span class="row-value">{{ $payment->ownership->display_name ?? '—' }}</span>
        </div>
        <div class="row">
            <span class="row-label">Buyer</span>
            <span class="row-value">{{ $payment->user->name }}</span>
        </div>
        <div class="row">
            <span class="row-label">Date paid</span>
            <span class="row-value">{{ $payment->paid_on->format('d F Y') }}</span>
        </div>
        <div class="row">
            <span class="row-label">Date logged</span>
            <span class="row-value">{{ $payment->created_at->format('d F Y · H:i') }}</span>
        </div>
        @if($payment->bank)
        <div class="row">
            <span class="row-label">Payment method</span>
            <span class="row-value">{{ $payment->bank }} Transfer</span>
        </div>
        @endif
        @if($payment->reference)
        <div class="row">
            <span class="row-label">Transaction ref</span>
            <span class="row-mono">{{ $payment->reference }}</span>
        </div>
        @endif
        <div class="row">
            <span class="row-label">Instalment</span>
            <span class="row-value">#{{ $payment->instalment_number }} of {{ $payment->ownership->total_instalments ?? '—' }}</span>
        </div>
    </div>

    <hr class="separator"/>

    {{-- Amount block --}}
    <div class="amount-block">
        <div class="amount-label">Amount paid</div>
        <div class="amount-value">{{ $money($payment->amount_kobo) }}</div>
    </div>

    {{-- Balance summary --}}
    <div class="section-label">Balance summary</div>
    <div style="margin-bottom:24px;">
        <div class="row">
            <span class="row-label">Agreed price</span>
            <span class="row-value">{{ $money($payment->ownership->agreed_price_kobo) }}</span>
        </div>
        <div class="row">
            <span class="row-label">Total paid to date</span>
            <span class="row-value">{{ $money($payment->ownership->paid_kobo) }}</span>
        </div>
        <div class="row">
            <span class="row-label">Outstanding balance</span>
            <span class="row-value">{{ $money($payment->ownership->outstanding_kobo) }}</span>
        </div>
        <div class="row">
            <span class="row-label">Percent paid</span>
            <span class="row-value">{{ number_format($payment->ownership->percent_paid, 1) }}%</span>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div>
            <div class="footer-note">
                <strong>This receipt is automatically generated</strong> and kept on permanent record in the Strongmas subscriber's portal.
                Scan the QR code to verify this receipt online, or log in at <strong>portal.strongmas.ng</strong>.
            </div>
            <div style="margin-top:10px;font-size:10px;color:#7a7469;">
                Strongmas Realty Ltd · hello@strongmas.ng · 0901 077 7777
            </div>
        </div>
        <div class="qr-box"></div>
    </div>

    {{-- Punch holes at bottom --}}
    <div class="punch-holes" style="margin-top:24px;margin-bottom:0;">
        <div class="punch"></div>
        <div class="punch"></div>
    </div>

</div>
</body>
</html>
