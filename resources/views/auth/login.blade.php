<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Sign In · Strongmas Subscriber's Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="login-grid" style="display:grid;grid-template-columns:1.1fr 1fr;min-height:100vh;">

    {{-- ── Left: Brand panel ─────────────────────────────────────────────── --}}
    <div class="login-brand" style="background:var(--hero-gradient);border-right:1px solid var(--line-tan);display:flex;flex-direction:column;justify-content:space-between;padding:40px 48px;overflow:hidden;">

        {{-- Logo --}}
        <div class="flex items-center gap-3">
            <img src="{{ asset('logo.png') }}" alt="Strongmas" style="width:40px;height:40px;object-fit:contain;border-radius:10px;border:1px solid var(--line-tan);">
            <div>
                <div style="font-weight:700;font-size:18px;color:var(--fg);line-height:1.1;">Strongmas</div>
                <div class="eyebrow" style="color:var(--fg-dim);">Residence & Development</div>
            </div>
        </div>

        {{-- Hero text --}}
        <div style="padding:24px 0;">
            <div class="eyebrow eyebrow-tan" style="margin-bottom:16px;">Your home, built in public</div>
            <h1 style="font-size:clamp(36px,4vw,52px);font-weight:700;color:var(--fg);line-height:1.08;letter-spacing:-0.025em;margin-bottom:20px;">
                Track every<br/>brick.<br/>Every naira.
            </h1>
            <p style="font-size:15px;color:var(--fg-mute);line-height:1.6;max-width:340px;">
                Log your payments, watch your home rise, and keep every receipt in one permanent, transparent place.
            </p>
        </div>

        {{-- Building viz --}}
        <div style="display:flex;justify-content:center;padding:16px 0;">
            <x-building-viz percent="62" variant="house" size="lg" :showLabel="false"/>
        </div>

        {{-- Footer stats --}}
        <div class="flex items-center gap-5 eyebrow" style="color:var(--fg-faint);">
            <span>Est. 2020</span><span>·</span><span>Lagos, NG</span><span>·</span><span>76+ Units Delivered</span>
        </div>
    </div>

    {{-- ── Right: Login form ─────────────────────────────────────────────── --}}
    <div style="background:var(--bg-card);display:flex;flex-direction:column;justify-content:center;padding:48px 56px;">
        <div style="max-width:380px;width:100%;margin:0 auto;">

            {{-- Logo mark (mobile / small branding) --}}
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:40px;">
                <img src="{{ asset('logo1.png') }}" alt="Strongmas Residence" style="height:28px;object-fit:contain;opacity:0.9;">
            </div>

            <div class="eyebrow" style="margin-bottom:8px;">Sign in</div>
            <h2 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:6px;">
                Pick up where you left off
            </h2>
            <p style="font-size:14px;color:var(--fg-mute);margin-bottom:36px;">
                Your property and payment history are waiting.
            </p>

            {{-- Error --}}
            @if($errors->any())
                <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.24);color:var(--danger);font-size:13px;border-radius:var(--r-md);padding:12px 16px;margin-bottom:20px;">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('status'))
                <div style="background:var(--info-bg);border:1px solid var(--line-tan);color:var(--tan);font-size:13px;border-radius:var(--r-md);padding:12px 16px;margin-bottom:20px;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:20px;">
                @csrf

                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-mute);margin-bottom:8px;">
                        Email or phone
                    </label>
                    <input type="text" name="email" value="{{ old('email') }}" required autofocus
                           class="input-base" placeholder="you@mail.com"/>
                </div>

                <div>
                    <div style="margin-bottom:8px;">
                        <label style="font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-mute);">
                            Password
                        </label>
                    </div>
                    <input type="password" name="password" required class="input-base" placeholder="••••••••"/>
                </div>

                <div class="flex items-center gap-2.5">
                    <input type="checkbox" name="remember" id="remember"
                           style="width:16px;height:16px;border-radius:4px;border:1px solid var(--line-strong);background:var(--bg-raised);accent-color:var(--tan);">
                    <label for="remember" style="font-size:14px;color:var(--fg-mute);">Keep me signed in</label>
                </div>

                <button type="submit" class="btn-primary" style="padding:14px 24px;font-size:15px;border-radius:var(--r-lg);justify-content:center;">
                    Sign in →
                </button>
            </form>

            <p style="text-align:center;font-size:12px;color:var(--fg-faint);margin-top:32px;">
                New buyer? Contact your sales rep to get access.
            </p>
        </div>
    </div>

</div>
</body>
</html>
