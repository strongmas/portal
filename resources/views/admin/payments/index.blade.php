<x-layouts.admin title="Payments">

<div class="page-header mb-6">
    <div>
        <div class="eyebrow" style="margin-bottom:6px;">Finance</div>
        <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">All payments</h1>
        <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">{{ $payments->total() }} total payments logged</p>
    </div>
    <div style="font-family:'Geist Mono',monospace;font-size:22px;font-weight:600;color:var(--tan);">{{ naira($totalKobo) }}</div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--line);">
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Receipt #</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Buyer</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Property</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Date paid</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Bank · Ref</th>
                <th style="padding:12px 20px;text-align:right;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Amount</th>
                <th style="padding:12px 20px;text-align:center;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                    onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 20px;">
                        <a href="{{ route('admin.payments.show', $payment) }}"
                           style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);text-decoration:none;"
                           onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">{{ $payment->receipt_number }}</a>
                    </td>
                    <td style="padding:14px 20px;">
                        <a href="{{ route('admin.buyers.show', $payment->user) }}"
                           style="font-weight:500;color:var(--fg);text-decoration:none;"
                           onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">{{ $payment->user->name }}</a>
                    </td>
                    <td style="padding:14px 20px;color:var(--fg-mute);">{{ $payment->ownership->display_name ?? '—' }}</td>
                    <td style="padding:14px 20px;color:var(--fg-dim);">{{ $payment->paid_on->format('d M Y') }}</td>
                    <td style="padding:14px 20px;color:var(--fg-dim);font-family:'Geist Mono',monospace;font-size:11px;">
                        {{ $payment->bank }}{{ $payment->bank && $payment->reference ? ' · ' : '' }}{{ $payment->reference }}
                    </td>
                    <td style="padding:14px 20px;text-align:right;font-family:'Geist Mono',monospace;font-weight:600;color:var(--fg);">{{ naira($payment->amount_kobo) }}</td>
                    <td style="padding:14px 20px;text-align:center;">
                        @if($payment->is_flagged)
                            <a href="{{ route('admin.payments.show', $payment) }}" class="chip chip-danger" style="font-size:10px;padding:2px 8px;text-decoration:none;">⚑ flagged</a>
                        @elseif($payment->proof_path)
                            <a href="{{ route('payments.proof', $payment) }}" target="_blank"
                               style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">view proof →</a>
                        @else
                            <span style="color:var(--fg-dim);font-size:12px;">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:60px;text-align:center;color:var(--fg-dim);font-size:14px;">No payments logged yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($payments->hasPages())
    <div style="margin-top:20px;display:flex;align-items:center;justify-content:space-between;font-size:13px;color:var(--fg-dim);">
        <span>Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}</span>
        <div class="flex gap-2">
            @if($payments->onFirstPage())
                <span class="btn-ghost" style="opacity:0.4;cursor:default;">← prev</span>
            @else
                <a href="{{ $payments->previousPageUrl() }}" class="btn-ghost" style="font-size:12px;padding:6px 14px;">← prev</a>
            @endif
            @if($payments->hasMorePages())
                <a href="{{ $payments->nextPageUrl() }}" class="btn-ghost" style="font-size:12px;padding:6px 14px;">next →</a>
            @else
                <span class="btn-ghost" style="opacity:0.4;cursor:default;">next →</span>
            @endif
        </div>
    </div>
@endif

</x-layouts.admin>
