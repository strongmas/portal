<x-layouts.admin title="Roles">
    <div style="max-width:860px;">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Roles</h1>
                <p style="font-size:13px;color:var(--fg-dim);margin-top:2px;">Define what each staff role can access</p>
            </div>
            <a href="{{ route('admin.roles.create') }}" class="btn-primary">+ Create Role</a>
        </div>

        @php $allPermissions = \App\Models\AdminRole::ALL_PERMISSIONS; @endphp

        <div class="card" style="padding:0;overflow:hidden;">
            @forelse($roles as $role)
            <div style="padding:20px;border-bottom:1px solid var(--line);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                            <p style="font-size:15px;font-weight:700;color:var(--fg);">{{ $role->name }}</p>
                            <span style="font-size:11px;color:var(--fg-mute);">{{ $role->users_count }} {{ Str::plural('member', $role->users_count) }}</span>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach($allPermissions as $key => $label)
                                <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:99px;border:1px solid {{ $role->hasPermission($key) ? 'var(--ok)' : 'var(--line)' }};color:{{ $role->hasPermission($key) ? 'var(--ok)' : 'var(--fg-faint)' }};background:{{ $role->hasPermission($key) ? 'rgba(93,217,167,0.08)' : 'transparent' }};">
                                    {{ $role->hasPermission($key) ? '✓' : '·' }} {{ $label }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn-secondary" style="font-size:12px;padding:6px 12px;">Edit</a>
                        @if($role->users_count === 0)
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:12px;padding:6px 12px;background:none;border:1px solid var(--danger);border-radius:8px;color:var(--danger);cursor:pointer;"
                                    onclick="return confirm('Delete role {{ $role->name }}?')">Delete</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div style="padding:40px;text-align:center;">
                <p style="font-size:14px;color:var(--fg-dim);">No roles yet.</p>
                <a href="{{ route('admin.roles.create') }}" style="font-size:13px;color:var(--hi);font-weight:600;">Create the first role →</a>
            </div>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
