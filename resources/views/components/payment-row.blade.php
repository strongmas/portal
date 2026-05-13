@props(['payment'])
<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid var(--line);"
     class="first:border-0">
    <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
        <div style="width:36px;height:36px;border-radius:10px;background:{{ $payment->is_flagged ? 'var(--danger-bg)' : 'var(--info-bg)' }};border:1px solid {{ $payment->is_flagged ? 'rgba(244,114,114,0.3)' : 'var(--line-tan)' }};display:grid;place-items:center;flex-shrink:0;">
            @if($payment->is_flagged)
                <span style="color:var(--danger);font-size:14px;">⚑</span>
            @else
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--tan)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            @endif
        </div>
        <div style="min-width:0;">
            <div style="font-size:14px;font-weight:500;color:{{ $payment->is_flagged ? 'var(--danger)' : 'var(--fg)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                {{ $payment->bank ? $payment->bank . ' · ' : '' }}{{ $payment->ownership->display_name ?? '—' }}
                @if($payment->is_flagged)
                    <span style="font-size:11px;font-weight:400;"> · Flagged by admin</span>
                @endif
            </div>
            <div style="font-size:12px;color:var(--fg-dim);margin-top:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                {{ $payment->paid_on->format('d M Y') }}
                @if($payment->reference)
                    · <span style="font-family:'Geist Mono',monospace;">{{ Str::limit($payment->reference, 20) }}</span>
                @endif
            </div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:16px;margin-left:16px;flex-shrink:0;">
        <span style="font-family:'Geist Mono',monospace;font-weight:600;color:{{ $payment->is_flagged ? 'var(--danger)' : 'var(--fg)' }};">{{ naira($payment->amount_kobo, true) }}</span>
        @if($payment->is_flagged)
            <a href="{{ route('payments.history') }}"
               style="font-size:12px;color:var(--danger);text-decoration:underline;text-underline-offset:3px;">Review →</a>
        @elseif($payment->proof_path)
            <a href="{{ route('payments.proof', $payment) }}"
               style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;"
               target="_blank">View proof</a>
        @endif
    </div>
</div>
