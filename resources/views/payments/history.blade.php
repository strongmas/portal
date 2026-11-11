<x-portal-layout>
    <x-slot:title>Payment History</x-slot>

    <div x-data="{ selected: {{ $payments->first()?->id ?? 'null' }}, confirmDelete: null }">

        {{-- Delete confirmation modal (only shown for flagged payments) --}}
        <div x-show="confirmDelete !== null" x-cloak
             style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);">
            <div class="card" style="padding:28px;max-width:420px;width:90%;margin:16px;">
                <h3 style="font-size:18px;font-weight:700;color:var(--fg);margin-bottom:8px;">Delete this payment?</h3>
                <p style="font-size:14px;color:var(--fg-mute);margin-bottom:20px;line-height:1.6;">
                    This will permanently remove the payment from your ledger and update your balance. This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <form method="POST" style="flex:1;" x-bind:action="'/payments/' + confirmDelete + '?_method=DELETE'">
                        @csrf
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;background:var(--danger);border-color:var(--danger);">
                            Yes, delete
                        </button>
                    </form>
                    <button type="button" @click="confirmDelete = null" class="btn-ghost" style="flex:1;justify-content:center;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <div class="page-header mb-8">
            <div>
                <div class="eyebrow" style="margin-bottom:6px;">Ledger</div>
                <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Your transparent ledger</h1>
                <p style="font-size:14px;color:var(--fg-mute);margin-top:4px;">Every payment you've logged, kept on permanent record.</p>
            </div>
            <div class="flex items-center gap-3">
                @php $flaggedCount = $payments->where('is_flagged', true)->count(); @endphp
                @if($flaggedCount > 0)
                    <span class="chip chip-danger">⚑ {{ $flaggedCount }} flagged</span>
                @endif
                <span class="chip chip-ok">{{ $payments->count() }} payments</span>
            </div>
        </div>

        @if($payments->isEmpty())
            <div class="card" style="padding:80px;text-align:center;color:var(--fg-dim);">
                <div style="font-size:40px;margin-bottom:12px;">📋</div>
                <h2 style="font-size:20px;font-weight:600;color:var(--fg);margin-bottom:8px;">No payments logged yet</h2>
                <p style="font-size:14px;color:var(--fg-mute);margin-bottom:24px;">Log your first payment to start building your transparent ledger.</p>
                <a href="{{ route('payments.create') }}" class="btn-primary">+ Log first payment</a>
            </div>
        @else
            <div class="history-grid">

                {{-- Timeline grouped by property --}}
                <div style="min-width:0;overflow:hidden;">
                    @foreach($grouped as $propertyName => $propertyPayments)
                        <div style="margin-bottom:28px;">
                            <div class="eyebrow timeline-month-label" style="margin-bottom:12px;position:sticky;top:64px;background:var(--bg);padding:4px 0;z-index:10;">
                                {{ $propertyName }}
                            </div>
                            <div style="position:relative;padding-left:32px;">
                                <div style="position:absolute;left:14px;top:0;bottom:0;width:1px;background:var(--line);"></div>
                                @foreach($propertyPayments as $payment)
                                    <div style="position:relative;margin-bottom:16px;">
                                        <div class="timeline-dot" :class="selected === {{ $payment->id }} ? 'timeline-dot-active' : ''"></div>
                                        <div class="card" style="margin-left:8px;padding:0;overflow:hidden;transition:border-color var(--dur-fast);"
                                             :style="selected === {{ $payment->id }} ? 'border-color:var(--line-tan);' : ''"
                                             style="{{ $payment->is_flagged ? 'border-color:rgba(244,114,114,0.4);' : '' }}">

                                            {{-- Flagged banner --}}
                                            @if($payment->is_flagged)
                                                <div style="background:var(--danger-bg);border-bottom:1px solid rgba(244,114,114,0.3);padding:10px 16px;display:flex;align-items:flex-start;gap:10px;">
                                                    <span style="color:var(--danger);font-size:14px;flex-shrink:0;">⚑</span>
                                                    <div style="flex:1;">
                                                        <div style="font-weight:600;color:var(--danger);font-size:13px;">Flagged as incorrect by admin</div>
                                                        <div style="font-size:12px;color:var(--fg-mute);margin-top:2px;">{{ $payment->flag_reason }}</div>
                                                    </div>
                                                </div>
                                            @endif

                                            <div style="padding:16px;" @click="selected = {{ $payment->id }}" class="cursor-pointer">
                                                <div class="timeline-card-row" style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                                                    <div style="flex:1;min-width:0;">
                                                        <div style="font-size:12px;color:var(--fg-dim);margin-bottom:4px;">{{ $payment->paid_on->format('d M Y') }}</div>
                                                        <div style="font-size:14px;font-weight:500;color:var(--fg);">
                                                            Instalment #{{ $payment->instalment_number }} logged
                                                        </div>
                                                        @if($payment->bank || $payment->reference)
                                                            @php
                                                                $bankRef = trim(($payment->bank ?? '') . ($payment->bank && $payment->reference ? ' · ' : '') . ($payment->reference ? Str::limit($payment->reference, 24) : ''));
                                                            @endphp
                                                            <div style="font-size:12px;color:var(--fg-dim);margin-top:4px;font-family:'Geist Mono',monospace;word-break:break-all;overflow-wrap:break-word;">
                                                                {{ $bankRef }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div style="text-align:right;flex-shrink:0;">
                                                        <div style="font-family:'Geist Mono',monospace;font-weight:600;font-size:15px;color:{{ $payment->is_flagged ? 'var(--danger)' : 'var(--fg)' }};">{{ naira($payment->amount_kobo, true) }}</div>
                                                        @if($payment->is_confirmed)
                                                            <span class="chip chip-ok" style="font-size:11px;padding:2px 8px;margin-top:4px;display:inline-block;">✓ Confirmed</span>
                                                        @endif
                                                        @if($payment->proof_path)
                                                            <a href="{{ route('payments.proof', $payment) }}" target="_blank"
                                                               class="chip" style="font-size:11px;padding:2px 8px;cursor:pointer;margin-top:4px;display:inline-block;" @click.stop>
                                                                View proof
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- Delete button — only shown when admin has flagged --}}
                                                @if($payment->is_flagged)
                                                <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;">
                                                    <button type="button"
                                                            @click.stop="confirmDelete = {{ $payment->id }}"
                                                            class="btn-ghost"
                                                            style="font-size:12px;padding:5px 12px;color:var(--danger);border-color:rgba(244,114,114,0.3);">
                                                        Delete payment
                                                    </button>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Detail panel (hidden on mobile) --}}
                <div class="history-receipt-panel" style="position:sticky;top:80px;align-self:start;">
                    @foreach($payments as $payment)
                        <div x-show="selected === {{ $payment->id }}" x-cloak>
                            <div class="eyebrow" style="margin-bottom:10px;">Payment Details</div>

                            @if($payment->is_flagged)
                                <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:var(--r-lg);padding:14px 16px;margin-bottom:12px;font-size:13px;color:var(--danger);">
                                    ⚑ This payment has been flagged. Please delete it and resubmit with the correct figures.
                                </div>
                            @endif

                            <div class="receipt" style="{{ $payment->is_flagged ? 'opacity:0.7;' : '' }}">
                                <div class="flex items-center justify-between" style="margin-bottom:16px;">
                                    <h3 style="font-size:15px;font-weight:600;color:var(--fg);">Strongmas Portal</h3>
                                </div>
                                <div class="divider"></div>
                                <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;margin-top:16px;">
                                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Property</span><strong style="color:var(--fg);">{{ $payment->ownership?->display_name ?? '—' }}</strong></div>
                                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Subscriber</span><strong style="color:var(--fg);">{{ $payment->user->name }}</strong></div>
                                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Date paid</span><strong style="color:var(--fg);">{{ $payment->paid_on->format('d M Y') }}</strong></div>
                                    @if($payment->bank)
                                        <div class="flex justify-between"><span style="color:var(--fg-dim);">Method</span><strong style="color:var(--fg);">{{ $payment->bank }} transfer</strong></div>
                                    @endif
                                    @if($payment->reference)
                                        <div class="flex justify-between gap-2"><span style="color:var(--fg-dim);flex-shrink:0;">Ref</span><span style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-mute);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:right;min-width:0;">{{ $payment->reference }}</span></div>
                                    @endif
                                    <div class="flex justify-between"><span style="color:var(--fg-dim);">Instalment</span><strong style="color:var(--fg);">#{{ $payment->instalment_number }}</strong></div>
                                    @if($payment->is_split)
                                        @php $siblings = $payment->groupSiblings(); @endphp
                                        <div style="padding:10px 12px;background:var(--bg-raised);border:1px solid var(--line);border-radius:var(--r-md);">
                                            <div style="font-size:11px;color:var(--fg-dim);margin-bottom:6px;">
                                                Part of one transfer of {{ naira($payment->group_total_kobo) }}, also covering:
                                            </div>
                                            @foreach($siblings as $sibling)
                                                <div class="flex justify-between gap-2" style="font-size:12px;">
                                                    <span style="color:var(--fg-mute);">{{ $sibling->ownership?->display_name ?? '—' }}</span>
                                                    <span style="font-family:'Geist Mono',monospace;color:var(--fg-mute);white-space:nowrap;">{{ naira($sibling->amount_kobo) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="divider"></div>
                                <div class="flex items-center justify-between">
                                    <span class="eyebrow">Amount</span>
                                    <span style="font-family:'Geist Mono',monospace;font-size:22px;font-weight:600;color:{{ $payment->is_flagged ? 'var(--danger)' : 'var(--tan)' }};">{{ naira($payment->amount_kobo) }}</span>
                                </div>
                            </div>

                            <div class="flex gap-3 mt-3">
                                @if($payment->proof_path)
                                    <a href="{{ route('payments.proof', $payment) }}" target="_blank"
                                       class="btn-primary" style="flex:1;justify-content:center;">View Proof</a>
                                @endif
                                @if($payment->is_flagged)
                                    <button type="button" @click="confirmDelete = {{ $payment->id }}"
                                            class="btn-ghost" style="flex:1;justify-content:center;color:var(--danger);border-color:rgba(244,114,114,0.3);">
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        @endif
    </div>

</x-portal-layout>
