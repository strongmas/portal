<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
.card{background:#fff;border-radius:8px;padding:32px;max-width:600px;margin:0 auto;}
h2{color:#1a1a1a;margin-top:0;}
.role-badge{display:inline-block;background:#e0f2fe;color:#0369a1;padding:4px 14px;border-radius:99px;font-size:13px;font-weight:600;text-transform:capitalize;}
.btn{display:inline-block;background:#1a1a1a;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:600;margin-top:20px;font-size:15px;}
.expires{margin-top:16px;font-size:12px;color:#999;}
</style></head>
<body>
<div class="card">
    <h2>You have been invited to a property</h2>
    <p><strong>{{ $invitation->inviter->name }}</strong> has invited you to access:</p>
    <p style="font-size:18px;font-weight:700;color:#1a1a1a;margin:8px 0;">{{ $invitation->ownership->display_name }}</p>

    <p>Your access role: <span class="role-badge">{{ ucfirst($invitation->role) }}</span></p>

    @if($invitation->role === 'owner')
    <p style="color:#444;font-size:14px;">As an <strong>Owner</strong>, you will have full access including the ability to manage group members.</p>
    @elseif($invitation->role === 'financial')
    <p style="color:#444;font-size:14px;">As a <strong>Financial</strong> member, you can view payment history and balances.</p>
    @else
    <p style="color:#444;font-size:14px;">As a <strong>Viewer</strong>, you can view property details and project updates.</p>
    @endif

    <a href="{{ route('invitations.show', $invitation->token) }}" class="btn">Accept Invitation</a>

    <p class="expires">This invitation expires on {{ $invitation->expires_at->format('d M Y \a\t g:i A') }}.</p>
    <p style="margin-top:24px;font-size:12px;color:#999;">Strongmas Portal</p>
</div>
</body></html>
