<x-layouts.admin title="KYC Verification">

<div class="page-header mb-6">
    <div>
        <div class="eyebrow" style="margin-bottom:6px;">Compliance</div>
        <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">KYC Verification Queue</h1>
        <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">{{ $pending->count() }} {{ Str::plural('buyer', $pending->count()) }} awaiting verification</p>
    </div>
</div>

@if($pending->isEmpty())
    <div class="card" style="padding:60px;text-align:center;background:var(--ok-bg);border-color:rgba(93,217,167,0.24);margin-bottom:24px;">
        <div style="font-size:36px;margin-bottom:12px;">✅</div>
        <h2 style="font-size:20px;font-weight:700;color:var(--ok);margin-bottom:6px;">All buyers verified</h2>
        <p style="font-size:13px;color:var(--fg-mute);">No KYC verifications pending right now.</p>
    </div>
@else
    <div style="margin-bottom:28px;">
        <div class="eyebrow" style="margin-bottom:12px;">Pending — {{ $pending->count() }}</div>
        <div class="card" style="padding:0;overflow:hidden;">
            @foreach($pending as $buyer)
                <div class="kyc-buyer-row first:border-0" style="display:flex;align-items:center;gap:16px;padding:18px 20px;border-top:1px solid var(--line);">
                    <div class="avatar" style="width:44px;height:44px;font-size:14px;flex-shrink:0;background:var(--warn-bg);border-color:rgba(245,194,107,0.3);color:var(--warn);">{{ $buyer->initials }}</div>
                    <div style="flex:1;min-width:0;">
                        <div class="flex items-center gap-3" style="margin-bottom:4px;">
                            <a href="{{ route('admin.buyers.show', $buyer) }}"
                               style="font-weight:600;color:var(--fg);text-decoration:none;"
                               onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">{{ $buyer->name }}</a>
                            <span class="chip chip-pending" style="font-size:10px;padding:2px 7px;">KYC pending</span>
                        </div>
                        <div style="font-size:13px;color:var(--fg-mute);">{{ $buyer->email }}</div>
                        <div class="flex items-center gap-4" style="margin-top:4px;font-size:12px;color:var(--fg-dim);">
                            @if($buyer->phone)<span>📞 {{ $buyer->phone }}</span>@endif
                            <span>{{ $buyer->ownerships_count }} {{ Str::plural('property', $buyer->ownerships_count) }}</span>
                            <span>Joined {{ $buyer->created_at->format('d M Y') }}</span>
                        </div>
                        @if($buyer->address)
                            <div style="font-size:12px;color:var(--fg-dim);margin-top:2px;">📍 {{ $buyer->address }}</div>
                        @endif
                    </div>
                    <div style="flex-shrink:0;text-align:right;font-size:12px;color:var(--fg-dim);margin-right:8px;">
                        @foreach($buyer->ownerships->take(2) as $o)
                            <div>{{ $o->display_name ?? '—' }}</div>
                        @endforeach
                        @if($buyer->ownerships->count() > 2)
                            <div style="color:var(--fg-dim);">+{{ $buyer->ownerships->count() - 2 }} more</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <form method="POST" action="{{ route('admin.kyc.approve', $buyer) }}">
                            @csrf
                            <button type="submit" class="btn-primary" style="background:var(--ok);border-color:var(--ok);">✓ Approve KYC</button>
                        </form>
                        <a href="{{ route('admin.buyers.show', $buyer) }}" class="btn-ghost" style="font-size:13px;">View profile</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if($verified->isNotEmpty())
    <div>
        <div class="eyebrow" style="margin-bottom:12px;">Recently verified</div>
        <div class="card" style="padding:0;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--line);">
                        <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Buyer</th>
                        <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Email</th>
                        <th style="padding:12px 20px;text-align:center;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Properties</th>
                        <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Verified on</th>
                        <th style="padding:12px 20px;text-align:right;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($verified as $buyer)
                        <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                            onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                            <td style="padding:12px 20px;">
                                <div class="flex items-center gap-3">
                                    <div class="avatar" style="width:28px;height:28px;font-size:10px;flex-shrink:0;">{{ $buyer->initials }}</div>
                                    <a href="{{ route('admin.buyers.show', $buyer) }}" style="font-weight:500;color:var(--fg);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">{{ $buyer->name }}</a>
                                </div>
                            </td>
                            <td style="padding:12px 20px;color:var(--fg-mute);">{{ $buyer->email }}</td>
                            <td style="padding:12px 20px;text-align:center;font-weight:600;color:var(--fg);">{{ $buyer->ownerships_count }}</td>
                            <td style="padding:12px 20px;font-size:12px;color:var(--fg-dim);">{{ $buyer->kyc_verified_at->format('d M Y') }}</td>
                            <td style="padding:12px 20px;text-align:right;">
                                <form method="POST" action="{{ route('admin.kyc.reject', $buyer) }}" class="inline"
                                      onsubmit="return confirm('Reset KYC for {{ $buyer->name }}? They will need to be re-verified.')">
                                    @csrf
                                    <button type="submit" style="font-size:12px;color:var(--danger);text-decoration:underline;text-underline-offset:3px;background:none;border:none;cursor:pointer;padding:0;">Reset KYC</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

</x-layouts.admin>
