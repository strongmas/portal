<x-portal-layout title="Notifications">
    <div style="max-width:680px;">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">Notifications</h1>
                <p style="color:var(--fg-dim);font-size:14px;margin:0;">Stay up to date with your property activity</p>
            </div>
            @if(auth()->user()->unreadNotifications->count())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" style="font-size:13px;color:var(--fg-dim);background:none;border:1px solid var(--line);padding:6px 14px;border-radius:8px;cursor:pointer;">
                    Mark all read
                </button>
            </form>
            @endif
        </div>

        @forelse($notifications as $notification)
        @php $data = $notification->data; @endphp
        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:12px;padding:18px 20px;margin-bottom:10px;display:flex;gap:14px;align-items:flex-start;{{ $notification->read_at ? '' : 'border-left:3px solid var(--hi);' }}">
            <div style="width:38px;height:38px;border-radius:10px;background:var(--bg-raised);display:grid;place-items:center;flex-shrink:0;">
                @if(($data['type'] ?? '') === 'appointment')
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                @elseif(($data['type'] ?? '') === 'project_update')
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                @elseif(($data['type'] ?? '') === 'document')
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                @else
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                @endif
            </div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 3px;">{{ $data['title'] ?? 'Notification' }}</p>
                <p style="font-size:13px;color:var(--fg-dim);margin:0 0 6px;line-height:1.5;">{{ $data['message'] ?? '' }}</p>
                <div style="display:flex;gap:12px;align-items:center;">
                    <span style="font-size:11px;color:var(--fg-mute);">{{ $notification->created_at->diffForHumans() }}</span>
                    @if(isset($data['url']))
                    <a href="{{ $data['url'] }}" style="font-size:12px;color:var(--hi);text-decoration:none;font-weight:500;">View →</a>
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">
                @csrf @method('DELETE')
                <button type="submit" style="background:none;border:none;color:var(--fg-mute);cursor:pointer;padding:4px;" title="Dismiss">✕</button>
            </form>
        </div>
        @empty
        <div style="text-align:center;padding:60px 20px;color:var(--fg-dim);">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;opacity:0.3;"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
            <p style="margin:0;font-size:15px;">No notifications yet</p>
        </div>
        @endforelse

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-portal-layout>
