<x-portal-layout>
    <x-slot:title>Profile</x-slot>

    <div style="margin-bottom:24px;">
        <div class="eyebrow" style="margin-bottom:6px;">Account</div>
        <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Profile & Settings</h1>
    </div>

    <div style="display:grid;gap:24px;grid-template-columns:300px 1fr;">

        {{-- ── Left: Profile card ─────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Avatar --}}
            <div class="card" style="padding:24px;text-align:center;">
                <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" id="avatar-form">
                    @csrf
                    <div style="position:relative;display:inline-block;cursor:pointer;" onclick="document.getElementById('avatar-input').click()">
                        @if($user->avatar_path)
                            <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="{{ $user->name }}"
                                 style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:2px solid var(--line-tan);">
                        @else
                            <div class="avatar lg" style="margin:0 auto;">{{ $user->initials }}</div>
                        @endif
                        <div style="position:absolute;bottom:0;right:0;width:26px;height:26px;border-radius:50%;background:var(--tan);color:var(--bg-raised);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;">✎</div>
                    </div>
                    <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp"
                           class="hidden" onchange="document.getElementById('avatar-form').submit()">
                </form>
                <p style="font-size:11px;color:var(--fg-dim);margin-top:8px;">Click photo to change</p>
                @error('avatar')<p style="color:var(--danger);font-size:12px;margin-top:6px;">{{ $message }}</p>@enderror

                <h2 style="font-size:18px;font-weight:700;color:var(--fg);margin-top:16px;">{{ $user->name }}</h2>
                <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">Buyer since {{ $user->member_since }}</p>
                <div class="flex items-center justify-center gap-2 mt-3">
                    @if($user->is_kyc_verified)
                        <span class="chip chip-ok">Verified KYC</span>
                    @else
                        <span class="chip chip-pending">KYC pending</span>
                    @endif
                    <span class="chip">{{ $user->ownerships->count() }} {{ Str::plural('property', $user->ownerships->count()) }}</span>
                </div>
            </div>

            {{-- Account info --}}
            <div class="card" style="padding:20px;" x-data="{ editing: false }">
                <div class="flex items-center justify-between" style="margin-bottom:16px;">
                    <h3 style="font-size:15px;font-weight:600;color:var(--fg);">Account info</h3>
                    <button @click="editing = !editing" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">
                        <span x-text="editing ? 'cancel' : 'edit ✎'"></span>
                    </button>
                </div>
                <div class="divider" style="margin-top:0;"></div>
                <div x-show="!editing" style="display:flex;flex-direction:column;gap:10px;font-size:13px;margin-top:16px;">
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Name</span><strong style="color:var(--fg);">{{ $user->name }}</strong></div>
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Email</span><strong style="color:var(--fg);">{{ $user->email }}</strong></div>
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Phone</span><strong style="color:var(--fg);">{{ $user->phone ?? '—' }}</strong></div>
                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Address</span><strong style="color:var(--fg);">{{ Str::limit($user->address ?? '—', 25) }}</strong></div>
                    @if($user->bvn_masked)
                        <div class="flex justify-between"><span style="color:var(--fg-dim);">BVN</span><span style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--fg-mute);">{{ $user->bvn_masked }}</span></div>
                    @endif
                </div>
                <form x-show="editing" method="POST" action="{{ route('profile.update') }}" style="display:flex;flex-direction:column;gap:12px;margin-top:16px;">
                    @csrf @method('PATCH')
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Full name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input-base" style="font-size:13px;"/>
                    </div>
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="input-base" style="font-size:13px;"/>
                    </div>
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Address</label>
                        <input type="text" name="address" value="{{ old('address', $user->address) }}" class="input-base" style="font-size:13px;"/>
                    </div>
                    <button type="submit" class="btn-primary" style="justify-content:center;">Save changes</button>
                </form>
            </div>

            {{-- Change password --}}
            <div class="card" style="padding:20px;" x-data="{ open: false }">
                <div class="flex items-center justify-between" style="margin-bottom:16px;">
                    <h3 style="font-size:15px;font-weight:600;color:var(--fg);">Change password</h3>
                    <button type="button" @click="open = !open"
                            style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;"
                            x-text="open ? 'cancel' : 'change ✎'"></button>
                </div>
                <div x-show="!open" style="font-size:14px;color:var(--fg-dim);letter-spacing:0.1em;">••••••••••••</div>
                <form x-show="open" x-cloak method="POST" action="{{ route('profile.password') }}" style="display:flex;flex-direction:column;gap:12px;">
                    @csrf @method('PUT')
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Current password</label>
                        <input type="password" name="current_password" required class="input-base" style="font-size:13px;" autocomplete="current-password"/>
                        @error('current_password')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">New password</label>
                        <input type="password" name="password" required minlength="8" class="input-base" style="font-size:13px;" autocomplete="new-password"/>
                        @error('password')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Confirm new password</label>
                        <input type="password" name="password_confirmation" required minlength="8" class="input-base" style="font-size:13px;" autocomplete="new-password"/>
                    </div>
                    <button type="submit" class="btn-primary" style="justify-content:center;">Update password</button>
                </form>
            </div>

        </div>

        {{-- ── Right: Properties ──────────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="flex items-center justify-between">
                <h2 style="font-size:22px;font-weight:600;color:var(--fg);">My properties</h2>
            </div>

            @forelse($user->ownerships as $ownership)
                <div class="card" style="padding:24px;">
                    <div class="prop-body" style="display:flex;gap:20px;">
                        <div style="flex-shrink:0;width:120px;height:90px;border-radius:var(--r-lg);background:var(--bg-raised);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;">
                            <x-building-viz :percent="$ownership->percent_paid" variant="house" size="sm" :showLabel="false"/>
                        </div>
                        <div style="flex:1;">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 style="font-size:17px;font-weight:700;color:var(--fg);">{{ $ownership->display_name }}</h3>
                                    <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">{{ $ownership->property->address }}</p>
                                    <p style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);margin-top:4px;">{{ $ownership->property->code }}</p>
                                </div>
                                <span class="chip chip-ok">active</span>
                            </div>
                            <div class="divider"></div>
                            <div class="wrap-row flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span style="font-size:13px;color:var(--fg-mute);">{{ number_format($ownership->percent_paid, 0) }}% paid</span>
                                    <div style="width:100px;"><x-progress-bar :percent="$ownership->percent_paid"/></div>
                                </div>
                                <a href="{{ route('properties.show', $ownership) }}" class="btn-secondary" style="font-size:12px;padding:6px 14px;">view →</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card" style="padding:60px;text-align:center;color:var(--fg-dim);">
                    <p style="font-size:14px;color:var(--fg-mute);">No properties linked yet. Contact your sales rep.</p>
                </div>
            @endforelse
        </div>

    </div>
</x-portal-layout>
