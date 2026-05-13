<x-portal-layout>
    <x-slot:title>Portfolio Balance</x-slot>

    <div class="page-header mb-8">
        <div>
            <div class="eyebrow" style="margin-bottom:6px;">Portfolio</div>
            <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Portfolio balance</h1>
            <p style="font-size:14px;color:var(--fg-mute);margin-top:4px;">Across {{ $ownerships->count() }} {{ Str::plural('property', $ownerships->count()) }}</p>
        </div>
        @if($ownedOwnerships->contains(fn($o) => $o->outstanding_kobo > 0))
            <a href="{{ route('payments.create') }}" class="btn-primary">+ Log payment</a>
        @endif
    </div>

    <div style="display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));">
        @foreach($ownerships as $ownership)
            @php
                $memberRole      = $memberRoles->get($ownership->id);
                $canSeeFinancials = $memberRole === null || in_array($memberRole, ['owner', 'financial']);
            @endphp
            <a href="{{ route('properties.show', $ownership) }}" class="card" style="display:block;text-decoration:none;transition:border-color var(--dur-fast);"
               onmouseover="this.style.borderColor='var(--line-tan)'" onmouseout="this.style.borderColor='var(--line)'">
                <div class="flex items-center justify-between" style="margin-bottom:20px;">
                    <h2 style="font-size:18px;font-weight:600;color:var(--fg);">{{ $ownership->display_name }}</h2>
                    @if($canSeeFinancials)
                        <span class="chip chip-tan">{{ number_format($ownership->percent_paid, 0) }}%</span>
                    @else
                        <span class="chip" style="font-size:11px;">{{ ucfirst($memberRole) }} Access</span>
                    @endif
                </div>
                @if($canSeeFinancials)
                <div class="balance-card-body flex items-end gap-5">
                    <x-building-viz :percent="$ownership->percent_paid" variant="house" size="md" :showLabel="false"/>
                    <div style="flex:1;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                            <span style="color:var(--fg-dim);">Total</span>
                            <span style="font-weight:600;color:var(--fg);font-family:'Geist Mono',monospace;">{{ naira($ownership->agreed_price_kobo) }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                            <span style="color:var(--fg-dim);">Paid</span>
                            <span style="font-weight:600;color:var(--tan);font-family:'Geist Mono',monospace;">{{ naira($ownership->paid_kobo) }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;">
                            <span style="color:var(--fg-dim);">Outstanding</span>
                            <span style="font-weight:600;color:var(--fg);font-family:'Geist Mono',monospace;">{{ naira($ownership->outstanding_kobo) }}</span>
                        </div>
                    </div>
                </div>
                <x-progress-bar :percent="$ownership->percent_paid" class="mt-4"/>
                @php $next = $ownership->next_due_instalment; @endphp
                @if($next)
                    <div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <div class="eyebrow" style="margin-bottom:2px;">Next</div>
                            <div style="font-size:14px;font-weight:500;color:var(--fg);">{{ $next->due_date->format('j M Y') }} · {{ naira($next->amount_kobo, true) }}</div>
                        </div>
                        <span style="font-size:13px;color:var(--fg-dim);">manage →</span>
                    </div>
                @endif
                @else
                    <p style="font-size:13px;color:var(--fg-mute);margin:0 0 12px;">Shared property access</p>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <span style="font-size:13px;color:var(--fg-dim);">Project Updates · Documents · Analytics →</span>
                    </div>
                @endif
            </a>
        @endforeach
    </div>

    @if($ownedOwnerships->count() > 1)
        @php
            $totalKobo = $ownedOwnerships->sum('agreed_price_kobo');
            $paidKobo  = $ownedOwnerships->sum('paid_kobo');
            $pct = $totalKobo > 0 ? round(($paidKobo / $totalKobo) * 100, 1) : 0;
        @endphp
        <div class="card card-brand" style="margin-top:20px;padding:24px;">
            <div class="flex items-center justify-between" style="margin-bottom:14px;">
                <div>
                    <div class="eyebrow" style="margin-bottom:4px;">Combined progress</div>
                    <div style="font-size:20px;font-weight:600;color:var(--fg);">
                        <span style="font-family:'Geist Mono',monospace;">{{ naira($paidKobo, true) }}</span> of <span style="font-family:'Geist Mono',monospace;">{{ naira($totalKobo, true) }}</span> paid · {{ $pct }}%
                    </div>
                </div>
            </div>
            <x-progress-bar :percent="$pct"/>
        </div>
    @endif

</x-portal-layout>
