<x-layouts.admin title="Appointments">
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="eyebrow" style="margin-bottom:4px;">Admin</div>
            <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Appointments</h1>
        </div>
        <div class="flex gap-2">
            @foreach([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','inspected'=>'Inspected','cancelled'=>'Cancelled'] as $val=>$label)
            <a href="{{ route('admin.appointments.index', $val ? ['status'=>$val] : []) }}"
               style="padding:6px 14px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid {{ request('status')===$val && ($val||!request('status')) ? 'var(--hi)' : 'var(--line)' }};background:{{ request('status')===$val && ($val||!request('status')) ? 'var(--hi)' : 'transparent' }};color:{{ request('status')===$val && ($val||!request('status')) ? '#fff' : 'var(--fg-dim)' }};text-decoration:none;">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid var(--line);background:var(--bg-raised);">
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Buyer</th>
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Property</th>
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Date & Time</th>
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Sales Rep</th>
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Status</th>
                    <th style="padding:10px 20px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $apt)
                <tr style="border-top:1px solid var(--line);" onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:12px 20px;">
                        <p style="font-weight:600;color:var(--fg);">{{ $apt->user->name }}</p>
                        <p style="color:var(--fg-mute);font-size:11px;">{{ $apt->user->phone }}</p>
                    </td>
                    <td style="padding:12px 20px;color:var(--fg-dim);">{{ $apt->property->development_name }}</td>
                    <td style="padding:12px 20px;color:var(--fg-dim);">
                        {{ $apt->preferred_date->format('d M Y') }}<br>
                        <span style="font-size:11px;color:var(--fg-mute);">{{ date('g:i A', strtotime($apt->preferred_time)) }}</span>
                    </td>
                    <td style="padding:12px 20px;color:var(--fg-dim);">{{ $apt->salesRep?->name ?? '—' }}</td>
                    <td style="padding:12px 20px;">
                        @php $status = $apt->status; @endphp
                        @if($status === 'confirmed')
                            <span class="chip chip-ok">{{ $apt->status_label }}</span>
                        @elseif($status === 'inspected')
                            <span class="chip chip-tan">{{ $apt->status_label }}</span>
                        @elseif($status === 'cancelled')
                            <span class="chip chip-danger">{{ $apt->status_label }}</span>
                        @elseif($status === 'rescheduled')
                            <span class="chip chip-pending">{{ $apt->status_label }}</span>
                        @else
                            <span class="chip chip-pending">{{ $apt->status_label }}</span>
                        @endif
                    </td>
                    <td style="padding:12px 20px;text-align:right;">
                        <a href="{{ route('admin.appointments.show', $apt) }}" style="font-size:12px;font-weight:600;color:var(--hi);text-decoration:none;">Manage →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div style="padding:60px;text-align:center;color:var(--fg-dim);font-size:13px;">No appointments yet.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $appointments->links() }}</div>
</x-layouts.admin>
