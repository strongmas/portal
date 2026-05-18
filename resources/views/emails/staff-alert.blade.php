<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $alertSubject }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #111827; padding: 20px 32px; display: flex; align-items: center; gap: 12px; }
        .header img { height: 32px; width: auto; }
        .header span { font-size: 13px; font-weight: 600; color: #fff; opacity: 0.7; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 12px; }
        .body p { font-size: 14px; color: #374151; line-height: 1.65; margin-bottom: 12px; }
        .cta-wrap { margin: 24px 0 8px; }
        .cta-btn { display: inline-block; background: #111827; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 32px; font-size: 11px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <img src="{{ $message->embed(public_path('logo.png')) }}" alt="Strongmas">
        <span>Admin Notification</span>
    </div>

    <div class="body">
        <h2>{{ $heading }}</h2>
        <p>{!! nl2br(e($body)) !!}</p>

        @if($actionUrl && $actionLabel)
        <div class="cta-wrap">
            <a href="{{ $actionUrl }}" class="cta-btn">{{ $actionLabel }} &rarr;</a>
        </div>
        @endif
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} Strongmas Residence &nbsp;·&nbsp; Admin Portal
    </div>
</div>
</body>
</html>
