<x-portal-layout title="Active Sessions">
    <div style="max-width:680px;">
        <div class="mb-6">
            <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">Active Sessions</h1>
            <p style="color:var(--fg-dim);font-size:14px;margin:0;">Devices currently signed in to your account</p>
        </div>

        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:14px;overflow:hidden;margin-bottom:20px;">
            @foreach($sessions as $session)
            <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;gap:14px;align-items:center;">
                <div style="width:36px;height:36px;border-radius:10px;background:{{ $session->is_current ? 'var(--ok)' : 'var(--bg-raised)' }}22;display:grid;place-items:center;flex-shrink:0;">
                    <svg width="14" height="14" fill="none" stroke="{{ $session->is_current ? 'var(--ok)' : 'currentColor' }}" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div style="flex:1;">
                    <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 2px;">
                        {{ Str::limit($session->agent, 60) }}
                        @if($session->is_current) <span style="font-size:11px;color:var(--ok);font-weight:500;">(This device)</span> @endif
                    </p>
                    <p style="font-size:12px;color:var(--fg-dim);margin:0;">
                        Last active {{ $session->last_seen->diffForHumans() }} · {{ $session->ip_address }}
                    </p>
                </div>
                @if(!$session->is_current)
                <form method="POST" action="{{ route('sessions.destroy', $session->id) }}">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size:12px;color:var(--danger);background:none;border:1px solid var(--danger);padding:4px 12px;border-radius:8px;cursor:pointer;">
                        End
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>

        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:14px;padding:24px;">
            <h3 style="font-size:15px;font-weight:700;color:var(--fg);margin:0 0 8px;">Log out all other devices</h3>
            <p style="font-size:13px;color:var(--fg-dim);margin:0 0 16px;">Enter your password to terminate all other active sessions.</p>
            <form method="POST" action="{{ route('sessions.destroy-others') }}" class="flex gap-3">
                @csrf
                <input type="password" name="password" placeholder="Your password"
                       style="flex:1;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--bg);color:var(--fg);font-size:14px;"
                       required />
                <button type="submit"
                        style="background:var(--danger);color:#fff;padding:10px 18px;border-radius:10px;border:none;font-size:13px;font-weight:600;cursor:pointer;">
                    End All Others
                </button>
            </form>
            @error('password') <p style="font-size:12px;color:var(--danger);margin:8px 0 0;">{{ $message }}</p> @enderror
        </div>
    </div>
</x-portal-layout>
