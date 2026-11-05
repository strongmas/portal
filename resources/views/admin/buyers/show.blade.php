<x-layouts.admin title="{{ $buyer->name }}">

@php $canManage = auth()->user()->isSuperAdmin(); @endphp

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.buyers.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Buyers</a>
    <span>/</span>
    <span style="color:var(--fg);">{{ $buyer->name }}</span>
</div>

<div class="flex items-start justify-between mb-6">
    <div class="flex items-center gap-4">
        <div class="avatar" style="width:52px;height:52px;font-size:16px;">{{ $buyer->initials }}</div>
        <div>
            <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">{{ $buyer->name }}</h1>
            <div class="flex items-center gap-2 mt-1">
                @if($buyer->is_kyc_verified)
                    <span class="chip chip-ok" style="font-size:11px;">✓ KYC Verified</span>
                @else
                    <span class="chip chip-pending" style="font-size:11px;">KYC pending</span>
                @endif
                <span style="font-size:12px;color:var(--fg-dim);">Member since {{ $buyer->member_since }}</span>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-3">
        @if(!$buyer->is_kyc_verified)
            <form method="POST" action="{{ route('admin.kyc.approve', $buyer) }}">
                @csrf
                <button type="submit" class="btn-primary" style="background:var(--ok);border-color:var(--ok);">✓ Approve KYC</button>
            </form>
        @endif
        @if($canManage)
            <form method="POST" action="{{ route('admin.buyers.reset-password', $buyer) }}"
                  onsubmit="return confirm('Reset password for {{ addslashes($buyer->name) }}? A new temporary password will be generated and emailed to them.')">
                @csrf
                <button type="submit" class="btn-ghost" style="color:var(--warn);border-color:var(--warn);">🔑 Reset password</button>
            </form>
            <a href="{{ route('admin.ownerships.create', ['buyer_id' => $buyer->id]) }}" class="btn-primary">🔗 Link property</a>
            <a href="{{ route('admin.buyers.edit', $buyer) }}" class="btn-ghost">Edit buyer</a>
        @endif
    </div>
</div>

<div class="grid gap-6" style="grid-template-columns:300px 1fr;">

    {{-- Left column --}}
    <div class="flex flex-col gap-4">

        {{-- Contact --}}
        <div class="card" style="padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:14px;">Contact &amp; identity</h3>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                @foreach([['Email', $buyer->email], ['Phone', $buyer->phone ?? '—'], ['Address', $buyer->address ?? '—']] as [$label, $val])
                    <div class="flex justify-between gap-3">
                        <span style="color:var(--fg-dim);flex-shrink:0;">{{ $label }}</span>
                        <span style="font-weight:500;color:var(--fg);text-align:right;overflow:hidden;text-overflow:ellipsis;">{{ $val }}</span>
                    </div>
                @endforeach
                @if($buyer->bvn_masked)
                    <div class="flex justify-between">
                        <span style="color:var(--fg-dim);">BVN</span>
                        <span style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--fg);">{{ $buyer->bvn_masked }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span style="color:var(--fg-dim);">KYC</span>
                    <span style="font-weight:500;color:{{ $buyer->is_kyc_verified ? 'var(--ok)' : 'var(--warn)' }};">
                        {{ $buyer->is_kyc_verified ? 'Verified ' . $buyer->kyc_verified_at->format('d M Y') : 'Pending' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Sales Rep --}}
        @php $allReps = \App\Models\SalesRep::where('is_active', true)->orderBy('name')->get(); @endphp
        <div class="card" style="padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:14px;">Sales representative</h3>
            @if($buyer->salesRep)
                <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;margin-bottom:14px;">
                    <div class="flex justify-between gap-3">
                        <span style="color:var(--fg-dim);">Name</span>
                        <span style="font-weight:600;color:var(--fg);">{{ $buyer->salesRep->name }}</span>
                    </div>
                    @if($buyer->salesRep->phone)
                    <div class="flex justify-between gap-3">
                        <span style="color:var(--fg-dim);">Phone</span>
                        <span style="color:var(--fg);">{{ $buyer->salesRep->phone }}</span>
                    </div>
                    @endif
                    @if($buyer->salesRep->email)
                    <div class="flex justify-between gap-3">
                        <span style="color:var(--fg-dim);">Email</span>
                        <span style="color:var(--fg);overflow:hidden;text-overflow:ellipsis;">{{ $buyer->salesRep->email }}</span>
                    </div>
                    @endif
                </div>
            @else
                <p style="font-size:13px;color:var(--fg-dim);margin-bottom:14px;">No sales rep assigned.</p>
            @endif
            @if($canManage)
                <form method="POST" action="{{ route('admin.buyers.assign-rep', $buyer) }}">
                    @csrf @method('POST')
                    <div style="display:flex;gap:8px;align-items:center;">
                        <select name="sales_rep_id" class="input-base" style="flex:1;font-size:13px;">
                            <option value="">— unassign —</option>
                            @foreach($allReps as $rep)
                                <option value="{{ $rep->id }}" {{ $buyer->sales_rep_id == $rep->id ? 'selected' : '' }}>{{ $rep->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-ghost" style="font-size:12px;padding:8px 12px;white-space:nowrap;">Assign</button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Financial summary --}}
        @php
            $totalPaid        = $buyer->ownerships->sum(fn($o) => $o->payments->sum('amount_kobo'));
            $totalAgreed      = $buyer->ownerships->sum('agreed_price_kobo');
            $totalOutstanding = max(0, $totalAgreed - $totalPaid);
            $pct              = $totalAgreed > 0 ? round(($totalPaid / $totalAgreed) * 100) : 0;
        @endphp
        <div class="card" style="padding:20px;">
            <h3 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:14px;">Financial summary</h3>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                <div class="flex justify-between"><span style="color:var(--fg-dim);">Properties</span><span style="font-weight:600;color:var(--fg);">{{ $buyer->ownerships->count() }}</span></div>
                <div class="flex justify-between"><span style="color:var(--fg-dim);">Total agreed</span><span style="font-weight:600;color:var(--fg);">{{ naira($totalAgreed) }}</span></div>
                <div class="flex justify-between"><span style="color:var(--fg-dim);">Total paid</span><span style="font-weight:600;color:var(--ok);">{{ naira($totalPaid) }}</span></div>
                <div class="flex justify-between"><span style="color:var(--fg-dim);">Outstanding</span><span style="font-weight:600;color:var(--fg);">{{ naira($totalOutstanding) }}</span></div>
                <div class="flex justify-between"><span style="color:var(--fg-dim);">Payments</span><span style="font-weight:600;color:var(--fg);">{{ $buyer->payments->count() }}</span></div>
            </div>
            @if($totalAgreed > 0)
                <div style="margin-top:14px;">
                    <div class="flex justify-between" style="font-size:12px;color:var(--fg-dim);margin-bottom:6px;">
                        <span>Overall progress</span><span>{{ $pct }}%</span>
                    </div>
                    <x-progress-bar :percent="$pct"/>
                </div>
            @endif
        </div>

        {{-- Support tickets --}}
        @if($buyer->supportTickets->isNotEmpty())
            <div class="card" style="padding:0;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--line);">
                    <h3 style="font-size:13px;font-weight:600;color:var(--fg);">Support tickets</h3>
                    <a href="{{ route('admin.tickets.index') }}" style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">All →</a>
                </div>
                @foreach($buyer->supportTickets as $ticket)
                    <a href="{{ route('admin.tickets.show', $ticket) }}"
                       style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);text-decoration:none;first:border-0;"
                       onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                        <div>
                            <div style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $ticket->case_no }}</div>
                            <div style="font-size:13px;color:var(--fg);">{{ Str::limit($ticket->category, 30) }}</div>
                        </div>
                        <span class="chip {{ match($ticket->status) { 'resolved' => 'chip-ok', 'in_progress' => 'chip-pending', default => 'chip-danger' } }}" style="font-size:10px;padding:2px 7px;">{{ $ticket->status }}</span>
                    </a>
                @endforeach
            </div>
        @endif

    </div>

    {{-- Right column: properties + payments --}}
    <div class="flex flex-col gap-5">

        @forelse($buyer->ownerships as $ownership)
            <div class="card" style="padding:0;overflow:hidden;">

                {{-- Property header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line);">
                    <div>
                        <div class="flex items-center gap-3" style="margin-bottom:4px;">
                            <h2 style="font-size:15px;font-weight:700;color:var(--fg);">{{ $ownership->display_name }}</h2>
                            <span style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $ownership->property->code ?? '' }}</span>
                            <span class="chip {{ $ownership->status === 'active' ? 'chip-ok' : 'chip-dark' }}" style="font-size:10px;padding:2px 7px;">{{ $ownership->status }}</span>
                        </div>
                        <p style="font-size:12px;color:var(--fg-dim);">{{ $ownership->property->address ?? '' }}</p>
                    </div>
                    <div class="flex items-center gap-5">
                        @foreach([['Agreed', naira($ownership->agreed_price_kobo), 'var(--fg)'], ['Paid', naira($ownership->paid_kobo), 'var(--ok)'], ['Outstanding', naira($ownership->outstanding_kobo), 'var(--fg)']] as [$lbl, $val, $clr])
                            <div style="text-align:right;">
                                <div style="font-size:11px;color:var(--fg-dim);">{{ $lbl }}</div>
                                <div style="font-weight:700;color:{{ $clr }};font-family:'Geist Mono',monospace;font-size:13px;">{{ $val }}</div>
                            </div>
                        @endforeach
                        @if($canManage)
                            <a href="{{ route('admin.ownerships.edit', $ownership) }}" class="btn-ghost" style="font-size:12px;padding:6px 12px;">Edit link</a>
                        @endif
                    </div>
                </div>

                {{-- Progress --}}
                <div style="padding:10px 20px;border-bottom:1px solid var(--line);background:var(--bg-raised);display:flex;align-items:center;gap:12px;">
                    <div style="flex:1;"><x-progress-bar :percent="$ownership->percent_paid"/></div>
                    <span style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--fg-dim);flex-shrink:0;">{{ number_format($ownership->percent_paid, 0) }}% paid</span>
                    <span style="font-size:12px;color:var(--fg-dim);flex-shrink:0;">{{ ucfirst($ownership->payment_plan) }} · {{ $ownership->total_instalments }} instalments</span>
                </div>

                {{-- Payments table --}}
                @if($ownership->payments->isNotEmpty())
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--line);">
                                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Receipt #</th>
                                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Date paid</th>
                                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Bank · Ref</th>
                                <th style="padding:10px 20px;text-align:right;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Amount</th>
                                <th style="padding:10px 20px;text-align:center;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Proof</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ownership->payments->sortByDesc('paid_on') as $payment)
                                <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                                    onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:10px 20px;font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">
                                        <a href="{{ route('admin.payments.show', $payment) }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">{{ $payment->receipt_number }}</a>
                                        @if($payment->is_flagged)<span class="chip chip-danger" style="font-size:9px;padding:1px 6px;margin-left:4px;">⚑</span>@endif
                                        @if($payment->is_confirmed)<span class="chip chip-ok" style="font-size:9px;padding:1px 6px;margin-left:4px;">✓ confirmed</span>@endif
                                    </td>
                                    <td style="padding:10px 20px;color:var(--fg-mute);">{{ $payment->paid_on->format('d M Y') }}</td>
                                    <td style="padding:10px 20px;color:var(--fg-dim);font-size:11px;font-family:'Geist Mono',monospace;">{{ $payment->bank }}{{ $payment->bank && $payment->reference ? ' · ' : '' }}{{ $payment->reference }}</td>
                                    <td style="padding:10px 20px;text-align:right;font-weight:700;color:var(--fg);font-family:'Geist Mono',monospace;">{{ naira($payment->amount_kobo) }}</td>
                                    <td style="padding:10px 20px;text-align:center;">
                                        @if($payment->proof_path)
                                            <a href="{{ route('payments.proof', $payment) }}" target="_blank" style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">view ↗</a>
                                        @else
                                            <span style="color:var(--fg-dim);font-size:12px;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding:24px;text-align:center;color:var(--fg-dim);font-size:13px;">No payments recorded yet for this property.</div>
                @endif

                {{-- Instalment schedule footer --}}
                @if($ownership->instalments->isNotEmpty())
                    @php
                        $paidCount    = $ownership->instalments->where('status', 'paid')->count();
                        $dueCount     = $ownership->instalments->where('status', 'due')->count();
                        $overdueCount = $ownership->instalments->where('status', 'overdue')->count();
                    @endphp
                    <div style="padding:10px 20px;background:var(--bg-raised);border-top:1px solid var(--line);display:flex;align-items:center;gap:16px;font-size:12px;color:var(--fg-dim);">
                        <span>📅 {{ $ownership->instalments->count() }} instalment schedule</span>
                        <span style="color:var(--ok);">✓ {{ $paidCount }} paid</span>
                        @if($dueCount > 0)<span style="color:var(--warn);">⏳ {{ $dueCount }} due</span>@endif
                        @if($overdueCount > 0)<span style="color:var(--danger);">⚠ {{ $overdueCount }} overdue</span>@endif
                        @if($canManage)
                            <a href="{{ route('admin.ownerships.edit', $ownership) }}" style="margin-left:auto;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">Manage schedule →</a>
                        @endif
                    </div>
                @else
                    <div style="padding:10px 20px;background:var(--warn-bg);border-top:1px solid rgba(245,194,107,0.2);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--warn);">
                        <span>⚠ No instalment schedule generated yet</span>
                        @if($canManage)
                            <a href="{{ route('admin.ownerships.edit', $ownership) }}" style="text-decoration:underline;text-underline-offset:3px;">Generate →</a>
                        @endif
                    </div>
                @endif

            </div>
        @empty
            <div class="card" style="padding:60px;text-align:center;color:var(--fg-dim);">
                No properties linked yet.
                @if($canManage)
                    <a href="{{ route('admin.ownerships.create', ['buyer_id' => $buyer->id]) }}" style="color:var(--tan);text-decoration:underline;text-underline-offset:3px;margin-left:4px;">Link a property →</a>
                @endif
            </div>
        @endforelse

    </div>
</div>

</x-layouts.admin>
