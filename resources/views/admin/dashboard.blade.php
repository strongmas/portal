<x-layouts.admin title="Dashboard">

<div class="page-header mb-8">
    <div>
        <div class="eyebrow" style="margin-bottom:6px;">{{ now()->format('l, j F Y') }}</div>
        <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Dashboard</h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.buyers.create') }}" class="btn-primary">+ Add buyer</a>
        <a href="{{ route('admin.properties.create') }}" class="btn-secondary">+ Add property</a>
    </div>
</div>

{{-- Stats grid --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:24px;">
    <a href="{{ route('admin.buyers.index') }}" class="card" style="display:block;text-decoration:none;transition:border-color var(--dur-fast);"
       onmouseover="this.style.borderColor='var(--line-tan)'" onmouseout="this.style.borderColor='var(--line)'">
        <div class="eyebrow" style="margin-bottom:8px;">Total buyers</div>
        <div style="font-family:'Geist Mono',monospace;font-size:36px;font-weight:600;color:var(--fg);letter-spacing:-0.04em;">{{ $buyerCount }}</div>
        <div style="font-size:12px;color:var(--fg-dim);margin-top:8px;">Manage →</div>
    </a>
    <a href="{{ route('admin.properties.index') }}" class="card" style="display:block;text-decoration:none;transition:border-color var(--dur-fast);"
       onmouseover="this.style.borderColor='var(--line-tan)'" onmouseout="this.style.borderColor='var(--line)'">
        <div class="eyebrow" style="margin-bottom:8px;">Properties</div>
        <div style="font-family:'Geist Mono',monospace;font-size:36px;font-weight:600;color:var(--fg);letter-spacing:-0.04em;">{{ $propertyCount }}</div>
        <div style="font-size:12px;color:var(--fg-dim);margin-top:8px;">{{ $ownershipCount }} active links →</div>
    </a>
    <div class="card">
        <div class="eyebrow" style="margin-bottom:8px;">Total collected</div>
        <div style="font-family:'Geist Mono',monospace;font-size:28px;font-weight:600;color:var(--tan);letter-spacing:-0.03em;">{{ naira($totalCollected, true) }}</div>
        <div style="font-size:12px;color:var(--fg-dim);margin-top:8px;">{{ naira(max(0,$totalOutstanding), true) }} outstanding</div>
    </div>
    <div class="card">
        @php
            $totalAgreed = $totalCollected + max(0, $totalOutstanding);
            $pct = $totalAgreed > 0 ? round(($totalCollected / $totalAgreed) * 100) : 0;
        @endphp
        <div class="eyebrow" style="margin-bottom:8px;">Overall progress</div>
        <div style="font-family:'Geist Mono',monospace;font-size:36px;font-weight:600;color:var(--fg);letter-spacing:-0.04em;">{{ $pct }}%</div>
        <x-progress-bar :percent="$pct" class="mt-3"/>
    </div>
</div>

{{-- Alert banners --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px;">
    @if($pendingKyc > 0)
        <a href="{{ route('admin.kyc.index') }}" class="card" style="display:flex;align-items:center;gap:16px;text-decoration:none;background:var(--warn-bg);border-color:rgba(245,194,107,0.24);transition:border-color var(--dur-fast);"
           onmouseover="this.style.borderColor='rgba(245,194,107,0.5)'" onmouseout="this.style.borderColor='rgba(245,194,107,0.24)'">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--warn-bg);border:1px solid rgba(245,194,107,0.4);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--warn);font-size:16px;flex-shrink:0;">{{ $pendingKyc }}</div>
            <div>
                <div style="font-weight:600;color:var(--warn);font-size:14px;">KYC verifications pending</div>
                <div style="font-size:12px;color:var(--fg-dim);margin-top:2px;">Buyers awaiting identity approval</div>
            </div>
            <span style="margin-left:auto;font-size:13px;color:var(--warn);">Review →</span>
        </a>
    @else
        <div class="card" style="display:flex;align-items:center;gap:16px;background:var(--ok-bg);border-color:rgba(93,217,167,0.24);">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--ok-bg);border:1px solid rgba(93,217,167,0.3);display:flex;align-items:center;justify-content:center;color:var(--ok);font-size:18px;font-weight:700;flex-shrink:0;">✓</div>
            <div><div style="font-weight:600;color:var(--ok);font-size:14px;">All KYC up to date</div><div style="font-size:12px;color:var(--fg-dim);margin-top:2px;">No pending verifications</div></div>
        </div>
    @endif
    @if($openTickets > 0)
        <a href="{{ route('admin.tickets.index') }}" class="card" style="display:flex;align-items:center;gap:16px;text-decoration:none;background:var(--danger-bg);border-color:rgba(244,114,114,0.24);"
           onmouseover="this.style.borderColor='rgba(244,114,114,0.5)'" onmouseout="this.style.borderColor='rgba(244,114,114,0.24)'">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--danger);font-size:16px;flex-shrink:0;">{{ $openTickets }}</div>
            <div>
                <div style="font-weight:600;color:var(--danger);font-size:14px;">Open support tickets</div>
                <div style="font-size:12px;color:var(--fg-dim);margin-top:2px;">Buyer complaints awaiting response</div>
            </div>
            <span style="margin-left:auto;font-size:13px;color:var(--danger);">Respond →</span>
        </a>
    @else
        <div class="card" style="display:flex;align-items:center;gap:16px;background:var(--ok-bg);border-color:rgba(93,217,167,0.24);">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--ok-bg);border:1px solid rgba(93,217,167,0.3);display:flex;align-items:center;justify-content:center;color:var(--ok);font-size:18px;font-weight:700;flex-shrink:0;">✓</div>
            <div><div style="font-weight:600;color:var(--ok);font-size:14px;">No open tickets</div><div style="font-size:12px;color:var(--fg-dim);margin-top:2px;">All complaints resolved</div></div>
        </div>
    @endif
</div>

{{-- 3-column content --}}
<div style="display:grid;gap:24px;grid-template-columns:1fr 1fr 280px;">

    {{-- Recent payments --}}
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--line);">
            <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Recent payments</h2>
            <a href="{{ route('admin.payments.index') }}" style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">All →</a>
        </div>
        @if($recentPayments->isEmpty())
            <div style="padding:40px;text-align:center;color:var(--fg-dim);font-size:13px;">No payments yet.</div>
        @else
            @foreach($recentPayments as $p)
                <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-top:1px solid var(--line);"
                     onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <div style="min-width:0;flex:1;">
                        <a href="{{ route('admin.buyers.show', $p->user) }}" style="font-size:14px;font-weight:500;color:var(--fg);text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                           onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">{{ $p->user->name }}</a>
                        <div style="font-size:12px;color:var(--fg-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p->ownership->display_name ?? '—' }} · {{ $p->paid_on->format('d M Y') }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-family:'Geist Mono',monospace;font-weight:600;color:var(--fg);font-size:14px;">{{ naira($p->amount_kobo) }}</div>
                        <div style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $p->receipt_number }}</div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Open complaints --}}
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--line);">
            <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Open complaints</h2>
            <a href="{{ route('admin.tickets.index') }}" style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">All tickets →</a>
        </div>
        @if($recentTickets->isEmpty())
            <div style="padding:60px;text-align:center;">
                <div style="font-size:28px;margin-bottom:10px;">🎉</div>
                <div style="font-size:13px;color:var(--fg-dim);">No open complaints</div>
            </div>
        @else
            @foreach($recentTickets as $ticket)
                <a href="{{ route('admin.tickets.show', $ticket) }}"
                   style="display:flex;align-items:flex-start;gap:12px;padding:14px 20px;border-top:1px solid var(--line);text-decoration:none;"
                   onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <span style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $ticket->case_no }}</span>
                            <span class="chip {{ $ticket->status === 'in_progress' ? 'chip-pending' : 'chip-danger' }}" style="font-size:10px;padding:2px 7px;">{{ $ticket->status }}</span>
                        </div>
                        <div style="font-size:14px;font-weight:500;color:var(--fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ticket->user->name }}</div>
                        <div style="font-size:12px;color:var(--fg-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit($ticket->category, 45) }}</div>
                    </div>
                    <span style="font-size:11px;color:var(--fg-dim);flex-shrink:0;margin-top:2px;">{{ $ticket->created_at->diffForHumans(null, true) }}</span>
                </a>
            @endforeach
        @endif
    </div>

    {{-- Quick actions + new buyers --}}
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card" style="padding:20px;">
            <h3 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:14px;">Quick actions</h3>
            <div style="display:flex;flex-direction:column;gap:2px;">
                <a href="{{ route('admin.buyers.create') }}" class="nav-item" style="font-size:13px;">👤 Create buyer account</a>
                <a href="{{ route('admin.properties.create') }}" class="nav-item" style="font-size:13px;">🏠 Add new property</a>
                <a href="{{ route('admin.ownerships.create') }}" class="nav-item" style="font-size:13px;">🔗 Link buyer → property</a>
                <a href="{{ route('admin.kyc.index') }}" class="nav-item" style="font-size:13px;">✅ KYC verification queue</a>
                <a href="{{ route('admin.tickets.index') }}" class="nav-item" style="font-size:13px;">💬 Manage complaints</a>
                <a href="{{ route('admin.support.index') }}" class="nav-item" style="font-size:13px;">📨 Buyer chat threads</a>
            </div>
        </div>

        <div class="card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--line);">
                <h3 style="font-size:14px;font-weight:600;color:var(--fg);">New buyers</h3>
                <a href="{{ route('admin.buyers.index') }}" style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">All →</a>
            </div>
            @forelse($recentBuyers as $buyer)
                <a href="{{ route('admin.buyers.show', $buyer) }}"
                   style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);text-decoration:none;"
                   onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <div class="avatar" style="font-size:11px;width:32px;height:32px;">{{ $buyer->initials }}</div>
                    <div style="min-width:0;flex:1;">
                        <div style="font-size:13px;font-weight:500;color:var(--fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $buyer->name }}</div>
                        <div style="font-size:11px;color:var(--fg-dim);">{{ $buyer->created_at->diffForHumans() }}</div>
                    </div>
                    @if(!$buyer->is_kyc_verified)
                        <span class="chip chip-pending" style="font-size:10px;padding:2px 7px;">KYC</span>
                    @endif
                </a>
            @empty
                <div style="padding:20px;text-align:center;font-size:13px;color:var(--fg-dim);">No buyers yet.</div>
            @endforelse
        </div>
    </div>
</div>

</x-layouts.admin>
