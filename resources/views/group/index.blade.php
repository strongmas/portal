<x-portal-layout title="Share Access">
<div style="max-width:720px;">

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;font-size:13px;color:var(--fg-dim);">
        <a href="{{ route('properties.show', $ownership) }}" style="color:var(--fg-dim);text-decoration:none;">{{ $ownership->display_name }}</a>
        <span>›</span>
        <span style="color:var(--fg);">Share Access</span>
    </div>

    {{-- Header --}}
    <div style="margin-bottom:28px;">
        <div class="eyebrow eyebrow-tan" style="margin-bottom:8px;">Family & Investor Access</div>
        <h1 style="font-size:26px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin:0 0 6px;">Share Property Access</h1>
        <p style="font-size:14px;color:var(--fg-mute);margin:0;">Invite family members or investors to view this property — {{ $ownership->display_name }}.</p>
    </div>

    {{-- Role explanation cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:28px;">
        {{-- Viewer --}}
        <div style="background:var(--bg-card);border:1px solid rgba(93,217,167,0.2);border-radius:14px;padding:16px;">
            <div style="width:32px;height:32px;border-radius:8px;background:rgba(93,217,167,0.12);display:grid;place-items:center;margin-bottom:10px;">
                <svg width="16" height="16" fill="none" stroke="#5DD9A7" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <p style="font-size:13px;font-weight:700;color:#5DD9A7;margin:0 0 4px;">Viewer</p>
            <p style="font-size:12px;color:var(--fg-dim);margin:0;line-height:1.5;">Read-only access. Can see project updates, value trends, and milestones. Cannot make payments or post comments.</p>
        </div>
        {{-- Financial --}}
        <div style="background:var(--bg-card);border:1px solid rgba(239,197,157,0.2);border-radius:14px;padding:16px;">
            <div style="width:32px;height:32px;border-radius:8px;background:rgba(239,197,157,0.12);display:grid;place-items:center;margin-bottom:10px;">
                <svg width="16" height="16" fill="none" stroke="var(--tan)" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <p style="font-size:13px;font-weight:700;color:var(--tan);margin:0 0 4px;">Financial</p>
            <p style="font-size:12px;color:var(--fg-dim);margin:0;line-height:1.5;">Can view all financial data including payment schedules, receipts, and balance details. Can also comment on updates.</p>
        </div>
        {{-- Co-Owner --}}
        <div style="background:var(--bg-card);border:1px solid rgba(99,148,244,0.2);border-radius:14px;padding:16px;">
            <div style="width:32px;height:32px;border-radius:8px;background:rgba(99,148,244,0.12);display:grid;place-items:center;margin-bottom:10px;">
                <svg width="16" height="16" fill="none" stroke="#7ba8f7" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
            <p style="font-size:13px;font-weight:700;color:#7ba8f7;margin:0 0 4px;">Co-Owner</p>
            <p style="font-size:12px;color:var(--fg-dim);margin:0;line-height:1.5;">Full access. Can manage group members, comment, and view all data. Cannot transfer primary ownership.</p>
        </div>
    </div>

    {{-- Invite form --}}
    @can('manage', $ownership)
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;padding:24px;margin-bottom:24px;">
        <h3 style="font-size:15px;font-weight:700;color:var(--fg);margin:0 0 16px;">Invite Someone</h3>
        @error('email')<p style="font-size:13px;color:var(--danger);background:var(--danger-bg);border:1px solid rgba(244,114,114,0.24);padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ $message }}</p>@enderror
        <form method="POST" action="{{ route('group.invite', $ownership) }}">
            @csrf
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <div style="flex:2;min-width:180px;">
                    <label style="font-size:12px;color:var(--fg-dim);display:block;margin-bottom:6px;">Email address</label>
                    <input name="email" type="email" placeholder="name@example.com" value="{{ old('email') }}"
                           class="input-base" required style="height:42px;" />
                </div>
                <div style="min-width:160px;">
                    <label style="font-size:12px;color:var(--fg-dim);display:block;margin-bottom:6px;">Access level</label>
                    <select name="role" class="input-base" style="height:42px;">
                        <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>Viewer (read-only)</option>
                        <option value="financial" {{ old('role') === 'financial' ? 'selected' : '' }}>Financial</option>
                        <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Co-Owner</option>
                    </select>
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="submit" class="btn-primary" style="height:42px;padding:0 20px;white-space:nowrap;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Send Invite
                    </button>
                </div>
            </div>
            <p style="font-size:12px;color:var(--fg-faint);margin:10px 0 0;">Invitation expires after 7 days. The recipient must create or log in to a Strongmas account to accept.</p>
        </form>
    </div>
    @endcan

    {{-- Members list --}}
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;overflow:hidden;margin-bottom:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;">
            <h3 style="font-size:14px;font-weight:700;color:var(--fg);margin:0;">Current Members</h3>
            <span style="font-size:12px;background:var(--bg-raised);color:var(--fg-dim);padding:3px 10px;border-radius:99px;">{{ $ownership->members->where('accepted_at','!=',null)->count() + 1 }}</span>
        </div>

        {{-- Primary owner --}}
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;gap:14px;align-items:center;">
            <div class="avatar">{{ $ownership->user->initials }}</div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 2px;">{{ $ownership->user->name }}</p>
                <p style="font-size:12px;color:var(--fg-dim);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $ownership->user->email }}</p>
            </div>
            <span style="font-size:12px;font-weight:600;background:rgba(99,148,244,0.12);color:#7ba8f7;border:1px solid rgba(99,148,244,0.3);padding:3px 12px;border-radius:99px;white-space:nowrap;">Primary Owner</span>
        </div>

        @foreach($ownership->members->where('accepted_at','!=',null) as $member)
        @php
            $roleColor = match($member->role) {
                'owner'     => ['bg'=>'rgba(99,148,244,0.12)', 'text'=>'#7ba8f7', 'border'=>'rgba(99,148,244,0.3)'],
                'financial' => ['bg'=>'rgba(239,197,157,0.12)', 'text'=>'var(--tan)', 'border'=>'rgba(239,197,157,0.24)'],
                default     => ['bg'=>'rgba(93,217,167,0.12)', 'text'=>'#5DD9A7', 'border'=>'rgba(93,217,167,0.24)'],
            };
        @endphp
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--bg-hover);display:grid;place-items:center;font-size:13px;font-weight:700;color:var(--fg-mute);flex-shrink:0;">{{ $member->user->initials }}</div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 2px;">{{ $member->user->name }}</p>
                <p style="font-size:12px;color:var(--fg-dim);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $member->user->email }}</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;flex-wrap:wrap;">
                @can('manage', $ownership)
                <form method="POST" action="{{ route('group.members.update-role', [$ownership, $member]) }}" style="display:flex;align-items:center;">
                    @csrf @method('PATCH')
                    <select name="role" onchange="this.form.submit()" style="padding:5px 10px;background:var(--bg-raised);border:1px solid var(--line);border-radius:8px;color:var(--fg);font-size:12px;cursor:pointer;height:32px;">
                        <option value="viewer"    {{ $member->role === 'viewer'    ? 'selected' : '' }}>Viewer</option>
                        <option value="financial" {{ $member->role === 'financial' ? 'selected' : '' }}>Financial</option>
                        <option value="owner"     {{ $member->role === 'owner'     ? 'selected' : '' }}>Co-Owner</option>
                    </select>
                </form>
                <form method="POST" action="{{ route('group.members.destroy', [$ownership, $member->user]) }}">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Remove {{ $member->user->name }} from this property?')"
                            style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:12px;padding:5px 10px;border-radius:6px;transition:background var(--dur-fast);"
                            onmouseover="this.style.background='var(--danger-bg)'" onmouseout="this.style.background='none'">
                        Remove
                    </button>
                </form>
                @else
                <span style="font-size:12px;font-weight:600;background:{{ $roleColor['bg'] }};color:{{ $roleColor['text'] }};border:1px solid {{ $roleColor['border'] }};padding:3px 12px;border-radius:99px;">{{ $member->role_label }}</span>
                @endcan
            </div>
        </div>
        @endforeach

        @if($ownership->members->where('accepted_at','!=',null)->count() === 0)
        <div style="padding:28px;text-align:center;color:var(--fg-dim);">
            <p style="font-size:14px;margin:0;">No additional members yet. Invite family or investors above.</p>
        </div>
        @endif
    </div>

    {{-- Pending invitations --}}
    @if($pendingInvitations->count())
    <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;overflow:hidden;margin-bottom:20px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:8px;">
            <h3 style="font-size:14px;font-weight:700;color:var(--fg);margin:0;">Pending Invitations</h3>
            <span style="font-size:12px;background:var(--warn-bg);color:var(--warn);border:1px solid rgba(245,194,107,0.24);padding:2px 8px;border-radius:99px;">{{ $pendingInvitations->count() }}</span>
        </div>
        @foreach($pendingInvitations as $inv)
        @php
            $roleBadge = match($inv->role) {
                'owner'     => ['bg'=>'rgba(99,148,244,0.12)', 'text'=>'#7ba8f7', 'border'=>'rgba(99,148,244,0.3)', 'label'=>'Co-Owner'],
                'financial' => ['bg'=>'rgba(239,197,157,0.12)', 'text'=>'var(--tan)', 'border'=>'rgba(239,197,157,0.24)', 'label'=>'Financial'],
                default     => ['bg'=>'rgba(93,217,167,0.12)', 'text'=>'#5DD9A7', 'border'=>'rgba(93,217,167,0.24)', 'label'=>'Viewer'],
            };
        @endphp
        <div style="padding:14px 20px;border-bottom:1px solid var(--line);display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--warn-bg);display:grid;place-items:center;flex-shrink:0;">
                <svg width="16" height="16" fill="none" stroke="var(--warn)" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:14px;color:var(--fg);margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $inv->email }}</p>
                <p style="font-size:12px;color:var(--fg-dim);margin:0;">Expires {{ $inv->expires_at->format('d M Y') }}</p>
            </div>
            <span style="font-size:12px;font-weight:600;background:{{ $roleBadge['bg'] }};color:{{ $roleBadge['text'] }};border:1px solid {{ $roleBadge['border'] }};padding:3px 12px;border-radius:99px;white-space:nowrap;">{{ $roleBadge['label'] }}</span>
            <span style="font-size:11px;color:var(--warn);background:var(--warn-bg);padding:3px 10px;border-radius:99px;font-weight:600;border:1px solid rgba(245,194,107,0.24);">Pending</span>
            @can('manage', $ownership)
            <form method="POST" action="{{ route('group.invitations.destroy', [$ownership, $inv]) }}">
                @csrf @method('DELETE')
                <button type="submit" onclick="return confirm('Cancel invitation to {{ $inv->email }}?')"
                        style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:12px;padding:5px 10px;border-radius:6px;transition:background var(--dur-fast);"
                        onmouseover="this.style.background='var(--danger-bg)'" onmouseout="this.style.background='none'">
                    Cancel
                </button>
            </form>
            @endcan
        </div>
        @endforeach
    </div>
    @endif

    {{-- Privacy note --}}
    <div style="margin-top:20px;padding:14px 16px;background:var(--bg-raised);border-radius:10px;border-left:3px solid var(--tan);display:flex;gap:10px;">
        <svg width="16" height="16" fill="none" stroke="var(--tan)" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <p style="font-size:13px;color:var(--fg-dim);margin:0;line-height:1.6;">
            <strong style="color:var(--fg);">Viewer access</strong> is read-only — invited viewers can see project updates, milestones, and value trends, but cannot submit payments, leave comments, or invite others.
        </p>
    </div>

</div>
</x-portal-layout>
