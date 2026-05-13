<x-portal-layout title="Login Activity">
    <div style="max-width:720px;">
        <div class="mb-6">
            <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">Login Activity</h1>
            <p style="color:var(--fg-dim);font-size:14px;margin:0;">Recent sign-ins to your account</p>
        </div>

        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:14px;overflow:hidden;">
            @forelse($activities as $activity)
            <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;gap:14px;align-items:center;">
                <div style="width:36px;height:36px;border-radius:10px;background:var(--bg-raised);display:grid;place-items:center;flex-shrink:0;">
                    @if($activity->device_type === 'Mobile')
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    @else
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    @endif
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 2px;">
                        {{ $activity->device_string }}
                    </p>
                    <p style="font-size:12px;color:var(--fg-dim);margin:0;">
                        {{ $activity->location }} · {{ $activity->ip_address }}
                    </p>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <p style="font-size:12px;color:var(--fg-dim);margin:0;">{{ $activity->logged_in_at->format('d M Y') }}</p>
                    <p style="font-size:11px;color:var(--fg-mute);margin:0;">{{ $activity->logged_in_at->format('g:i A') }}</p>
                </div>
            </div>
            @empty
            <div style="padding:48px;text-align:center;color:var(--fg-dim);">No login activity recorded yet.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $activities->links() }}</div>
    </div>
</x-portal-layout>
