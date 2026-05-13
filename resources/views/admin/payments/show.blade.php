<x-layouts.admin title="{{ $payment->receipt_number }}">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.payments.index') }}" style="color:var(--fg-dim);text-decoration:none;"
       onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Payments</a>
    <span>/</span>
    <span style="color:var(--fg);font-family:'Geist Mono',monospace;">{{ $payment->receipt_number }}</span>
</div>

@if($payment->is_flagged)
    <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:var(--r-lg);padding:16px 20px;display:flex;align-items:flex-start;gap:14px;margin-bottom:24px;">
        <div style="width:36px;height:36px;border-radius:50%;background:rgba(244,114,114,0.15);border:1px solid rgba(244,114,114,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;">⚑</div>
        <div style="flex:1;">
            <div style="font-weight:600;color:var(--danger);font-size:14px;margin-bottom:4px;">Transaction flagged as incorrect</div>
            <div style="font-size:13px;color:var(--fg-mute);">{{ $payment->flag_reason }}</div>
            <div style="font-size:11px;color:var(--fg-dim);margin-top:6px;font-family:'Geist Mono',monospace;">Flagged {{ $payment->flagged_at->diffForHumans() }}</div>
        </div>
        <form method="POST" action="{{ route('admin.payments.unflag', $payment) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn-ghost" style="font-size:12px;padding:6px 14px;color:var(--danger);border-color:rgba(244,114,114,0.3);"
                    onclick="return confirm('Remove this flag?')">Remove flag</button>
        </form>
    </div>
@endif

<div style="max-width:760px;">
    <div class="flex items-start justify-between mb-6">
        <div>
            <div style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--fg-dim);margin-bottom:4px;">{{ $payment->receipt_number }}</div>
            <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">{{ naira($payment->amount_kobo) }}</h1>
            <p style="font-size:13px;color:var(--fg-mute);margin-top:4px;">
                Paid {{ $payment->paid_on->format('d F Y') }} · by
                <a href="{{ route('admin.buyers.show', $payment->user) }}" style="color:var(--tan);text-decoration:underline;text-underline-offset:3px;">{{ $payment->user->name }}</a>
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if($payment->is_flagged)
                <span class="chip chip-danger">⚑ Flagged</span>
            @endif
            <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="btn-secondary" style="font-size:13px;">
                ⇩ PDF receipt
            </a>
        </div>
    </div>

    {{-- Transaction details --}}
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:20px;">
        <div style="padding:18px 20px;border-bottom:1px solid var(--line);">
            <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Transaction details</h2>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
            @php
                $rows = [
                    ['Buyer',       '<a href="'.route('admin.buyers.show', $payment->user).'" style="color:var(--tan);text-decoration:underline;text-underline-offset:3px;">'.$payment->user->name.'</a>'],
                    ['Property',    e($payment->ownership->display_name ?? '—')],
                    ['Date paid',   $payment->paid_on->format('d F Y')],
                    ['Date logged', $payment->created_at->format('d M Y · H:i')],
                    ['Bank',        $payment->bank ? e($payment->bank) : '—'],
                    ['Reference',   $payment->reference ? '<span style="font-family:\'Geist Mono\',monospace;font-size:12px;">'.e($payment->reference).'</span>' : '—'],
                    ['Payment plan', ucfirst($payment->ownership->payment_plan)],
                    ['Instalment',  '#'.$payment->instalment_number.' of '.$payment->ownership->total_instalments],
                ];
            @endphp
            @foreach($rows as [$label, $val])
                <div style="padding:14px 20px;border-bottom:1px solid var(--line);@if($loop->even)border-left:1px solid var(--line);@endif">
                    <div style="font-size:11px;color:var(--fg-dim);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em;font-family:'Geist Mono',monospace;">{{ $label }}</div>
                    <div style="font-size:14px;font-weight:500;color:var(--fg);">{!! $val !!}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Balance snapshot --}}
    <div class="card" style="padding:20px;margin-bottom:20px;">
        <h2 style="font-size:15px;font-weight:600;color:var(--fg);margin-bottom:16px;">Balance snapshot</h2>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
            <div style="background:var(--bg-raised);border:1px solid var(--line);border-radius:var(--r-md);padding:14px;text-align:center;">
                <div style="font-size:11px;color:var(--fg-dim);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.05em;">Agreed price</div>
                <div style="font-family:'Geist Mono',monospace;font-weight:600;color:var(--fg);">{{ naira($payment->ownership->agreed_price_kobo) }}</div>
            </div>
            <div style="background:rgba(93,217,167,0.08);border:1px solid rgba(93,217,167,0.2);border-radius:var(--r-md);padding:14px;text-align:center;">
                <div style="font-size:11px;color:var(--fg-dim);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.05em;">Total paid</div>
                <div style="font-family:'Geist Mono',monospace;font-weight:600;color:var(--ok);">{{ naira($payment->ownership->paid_kobo) }}</div>
            </div>
            <div style="background:var(--bg-raised);border:1px solid var(--line);border-radius:var(--r-md);padding:14px;text-align:center;">
                <div style="font-size:11px;color:var(--fg-dim);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.05em;">Outstanding</div>
                <div style="font-family:'Geist Mono',monospace;font-weight:600;color:var(--fg);">{{ naira($payment->ownership->outstanding_kobo) }}</div>
            </div>
        </div>
        <x-progress-bar :percent="$payment->ownership->percent_paid"/>
        <div style="font-size:12px;color:var(--fg-dim);margin-top:6px;text-align:right;">{{ number_format($payment->ownership->percent_paid, 1) }}% paid</div>
    </div>

    {{-- Proof of payment --}}
    <div class="card" style="padding:20px;margin-bottom:20px;">
        <div class="flex items-center justify-between">
            <div>
                <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Proof of payment</h2>
                <p style="font-size:12px;color:var(--fg-dim);margin-top:2px;">Transfer receipt or deposit slip uploaded by buyer</p>
            </div>
            @if($payment->proof_path)
                <a href="{{ route('payments.proof', $payment) }}" target="_blank" class="btn-secondary" style="font-size:13px;">
                    View proof →
                </a>
            @else
                <span class="chip chip-pending">No proof uploaded</span>
            @endif
        </div>
    </div>

    @if($payment->notes)
        <div class="card" style="padding:20px;margin-bottom:20px;">
            <div style="font-size:11px;color:var(--fg-dim);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em;font-family:'Geist Mono',monospace;">Notes from buyer</div>
            <p style="font-size:14px;color:var(--fg-mute);line-height:1.6;">{{ $payment->notes }}</p>
        </div>
    @endif

    {{-- Flag / unflag panel --}}
    @if(!$payment->is_flagged)
        <div class="card" style="padding:20px;border-color:rgba(244,114,114,0.2);" x-data="{ open: false }">
            <div class="flex items-center justify-between" style="margin-bottom:0;" :style="open ? 'margin-bottom:16px;' : ''">
                <div>
                    <h3 style="font-size:14px;font-weight:600;color:var(--fg);">Flag transaction as incorrect</h3>
                    <p style="font-size:12px;color:var(--fg-dim);margin-top:2px;">Use when the amount or proof doesn't match. The buyer will see this and can delete and resubmit.</p>
                </div>
                <button type="button" @click="open = !open" class="btn-ghost" style="font-size:12px;padding:6px 14px;color:var(--danger);border-color:rgba(244,114,114,0.3);flex-shrink:0;margin-left:16px;">
                    ⚑ Flag
                </button>
            </div>
            <form x-show="open" x-cloak method="POST" action="{{ route('admin.payments.flag', $payment) }}" style="display:flex;flex-direction:column;gap:12px;">
                @csrf @method('PATCH')
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Reason for flagging</label>
                    <textarea name="flag_reason" required rows="3" class="input-base" placeholder="e.g. Amount entered (₦500,000) does not match the proof of payment shown (₦350,000). Please delete and resubmit the correct figure." style="font-size:13px;resize:vertical;"></textarea>
                    @error('flag_reason')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary" style="background:var(--danger);flex-shrink:0;" onclick="return confirm('Flag this transaction? The buyer will see the reason and can delete it.')">Confirm flag</button>
                    <button type="button" @click="open = false" class="btn-ghost" style="font-size:13px;">Cancel</button>
                </div>
            </form>
        </div>
    @endif

</div>

</x-layouts.admin>
