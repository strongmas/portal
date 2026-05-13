<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Property Invitation · Strongmas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background:#f5f5f5;min-height:100vh;display:grid;place-items:center;padding:20px;">
<div style="background:#fff;border-radius:16px;padding:40px;max-width:480px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <div style="text-align:center;margin-bottom:28px;">
        <img src="{{ asset('logo.png') }}" alt="Strongmas" style="width:48px;height:48px;object-fit:contain;margin-bottom:12px;">
        <h1 style="font-size:20px;font-weight:700;color:#1a1a1a;margin:0 0 6px;">Property Access Invitation</h1>
        <p style="color:#666;font-size:14px;margin:0;">{{ $invitation->inviter->name }} has invited you</p>
    </div>

    <div style="background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:24px;text-align:center;">
        <p style="font-size:18px;font-weight:700;color:#1a1a1a;margin:0 0 6px;">{{ $invitation->ownership->display_name }}</p>
        <span style="font-size:13px;font-weight:600;background:#e0f2fe;color:#0369a1;padding:3px 12px;border-radius:99px;text-transform:capitalize;">
            {{ $invitation->role }} Access
        </span>
    </div>

    @php $existingUser = \App\Models\User::where('email', $invitation->email)->exists(); @endphp

    @if($existingUser)
    <p style="font-size:14px;color:#444;text-align:center;margin:0 0 20px;">Click below to accept access to this property.</p>
    <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}">
        @csrf
        <button type="submit"
                style="width:100%;background:#1a1a1a;color:#fff;padding:14px;border-radius:10px;border:none;font-size:15px;font-weight:600;cursor:pointer;">
            Accept Invitation
        </button>
    </form>
    @else
    <p style="font-size:14px;color:#444;margin:0 0 20px;">Create your account to accept this invitation.</p>
    <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}">
        @csrf
        <div style="margin-bottom:14px;">
            <label style="font-size:13px;font-weight:600;color:#1a1a1a;display:block;margin-bottom:6px;">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;font-size:14px;box-sizing:border-box;">
            @error('name') <p style="font-size:12px;color:#ef4444;margin:4px 0 0;">{{ $message }}</p> @enderror
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-size:13px;font-weight:600;color:#1a1a1a;display:block;margin-bottom:6px;">Email</label>
            <input type="email" value="{{ $invitation->email }}" disabled
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;font-size:14px;background:#f8f9fa;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-size:13px;font-weight:600;color:#1a1a1a;display:block;margin-bottom:6px;">Password</label>
            <input type="password" name="password" required minlength="8"
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;font-size:14px;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:20px;">
            <label style="font-size:13px;font-weight:600;color:#1a1a1a;display:block;margin-bottom:6px;">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                   style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;font-size:14px;box-sizing:border-box;">
        </div>
        <button type="submit"
                style="width:100%;background:#1a1a1a;color:#fff;padding:14px;border-radius:10px;border:none;font-size:15px;font-weight:600;cursor:pointer;">
            Create Account & Accept
        </button>
    </form>
    @endif

    <p style="text-align:center;font-size:12px;color:#999;margin-top:20px;">
        Expires {{ $invitation->expires_at->format('d M Y') }}
    </p>
</div>
</body>
</html>
