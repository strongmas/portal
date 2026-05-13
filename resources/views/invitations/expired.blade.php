<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"/><title>Expired Invitation · Strongmas</title>@vite(['resources/css/app.css'])</head>
<body style="background:#f5f5f5;min-height:100vh;display:grid;place-items:center;padding:20px;">
<div style="background:#fff;border-radius:16px;padding:40px;max-width:400px;width:100%;text-align:center;">
    <div style="width:56px;height:56px;background:#fef2f2;border-radius:50%;display:grid;place-items:center;margin:0 auto 16px;">
        <svg width="24" height="24" fill="none" stroke="#ef4444" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    <h2 style="color:#1a1a1a;margin:0 0 8px;">Invitation Expired</h2>
    <p style="color:#666;font-size:14px;margin:0 0 24px;">This invitation link has expired. Please ask the property owner to send a new invitation.</p>
    <a href="{{ route('login') }}" style="background:#1a1a1a;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;">
        Go to Login
    </a>
</div>
</body>
</html>
