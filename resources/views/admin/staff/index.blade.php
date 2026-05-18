<x-layouts.admin title="Staff">
    <div style="max-width:860px;">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Staff Accounts</h1>
                <p style="font-size:13px;color:var(--fg-dim);margin-top:2px;">Sub-admin users with restricted access</p>
            </div>
            <a href="{{ route('admin.staff.create') }}" class="btn-primary">+ Add Staff</a>
        </div>

        <div class="card" style="padding:0;overflow:hidden;">
            @forelse($staff as $member)
            <div style="display:flex;align-items:center;gap:16px;padding:16px 20px;border-bottom:1px solid var(--line);">
                <div style="width:40px;height:40px;border-radius:50%;background:var(--hi);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#000;flex-shrink:0;">{{ $member->initials }}</div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:600;color:var(--fg);">{{ $member->name }}</p>
                    <p style="font-size:12px;color:var(--fg-dim);">{{ $member->email }}</p>
                </div>
                <div style="flex-shrink:0;">
                    @if($member->adminRole)
                        <span style="font-size:11px;font-weight:600;background:var(--bg-raised);color:var(--hi);border:1px solid var(--line);border-radius:99px;padding:3px 10px;">{{ $member->adminRole->name }}</span>
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a href="{{ route('admin.staff.edit', $member) }}" class="btn-secondary" style="font-size:12px;padding:6px 12px;">Edit</a>
                    <form method="POST" action="{{ route('admin.staff.reset-password', $member) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn-secondary" style="font-size:12px;padding:6px 12px;"
                                onclick="return confirm('Reset password for {{ $member->name }}?')">Reset PW</button>
                    </form>
                    <form method="POST" action="{{ route('admin.staff.destroy', $member) }}" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size:12px;padding:6px 12px;background:none;border:1px solid var(--danger);border-radius:8px;color:var(--danger);cursor:pointer;"
                                onclick="return confirm('Remove {{ $member->name }}?')">Remove</button>
                    </form>
                </div>
            </div>
            @empty
            <div style="padding:40px;text-align:center;">
                <p style="font-size:14px;color:var(--fg-dim);">No staff accounts yet.</p>
                <a href="{{ route('admin.staff.create') }}" style="font-size:13px;color:var(--hi);font-weight:600;">Add the first staff member →</a>
            </div>
            @endforelse
        </div>

        <div style="margin-top:24px;padding:16px 20px;background:var(--bg-raised);border:1px solid var(--line);border-radius:12px;">
            <p style="font-size:13px;color:var(--fg-dim);">
                Staff accounts share the same admin login at <strong>/admin</strong> but only see the sections their role allows.
                Manage what each role can access in <a href="{{ route('admin.roles.index') }}" style="color:var(--hi);font-weight:600;">Roles</a>.
            </p>
        </div>
    </div>
</x-layouts.admin>
