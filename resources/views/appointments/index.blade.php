<x-portal-layout title="My Appointments">
    <div style="max-width:800px;">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">Appointments</h1>
                <p style="color:var(--fg-dim);font-size:14px;margin:0;">Your site inspection bookings</p>
            </div>
            @if(auth()->user()->hasOwnerAccess())
            <a href="{{ route('appointments.create') }}"
               style="background:var(--fg);color:var(--bg);padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                + Book Inspection
            </a>
            @endif
        </div>

        @forelse($appointments as $appointment)
        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:14px;padding:20px 22px;margin-bottom:12px;">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p style="font-size:15px;font-weight:700;color:var(--fg);margin:0 0 4px;">
                        {{ $appointment->property->development_name }}
                    </p>
                    <p style="font-size:13px;color:var(--fg-dim);margin:0 0 8px;">
                        📅 {{ $appointment->date_time }}
                    </p>
                    @if($appointment->salesRep)
                    <p style="font-size:12px;color:var(--fg-mute);margin:0;">
                        Rep: {{ $appointment->salesRep->name }}
                    </p>
                    @endif
                    @if($appointment->notes)
                    <p style="font-size:12px;color:var(--fg-dim);margin:6px 0 0;font-style:italic;">{{ $appointment->notes }}</p>
                    @endif
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                    @php
                        $colors = ['confirmed'=>'#22c55e','inspected'=>'#3b82f6','cancelled'=>'#ef4444','rescheduled'=>'#f97316','pending'=>'#eab308'];
                        $color = $colors[$appointment->status] ?? '#6b7280';
                    @endphp
                    <span style="font-size:12px;font-weight:600;color:{{ $color }};background:{{ $color }}18;padding:3px 10px;border-radius:99px;">
                        {{ $appointment->status_label }}
                    </span>
                    @if(in_array($appointment->status, ['pending','confirmed']))
                    <form method="POST" action="{{ route('appointments.cancel', $appointment) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                onclick="return confirm('Cancel this inspection?')"
                                style="font-size:12px;color:var(--danger);background:none;border:1px solid var(--danger);padding:4px 12px;border-radius:8px;cursor:pointer;">
                            Cancel
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @if($appointment->outcome_notes)
            <div style="margin-top:12px;padding:12px;background:var(--bg-raised);border-radius:8px;">
                <p style="font-size:12px;font-weight:600;color:var(--fg-dim);margin:0 0 4px;">Inspection Outcome</p>
                <p style="font-size:13px;color:var(--fg);margin:0;">{{ $appointment->outcome_notes }}</p>
            </div>
            @endif
        </div>
        @empty
        <div style="text-align:center;padding:60px;color:var(--fg-dim);">
            <p style="font-size:15px;margin:0 0 16px;">No appointments yet</p>
            @if(auth()->user()->hasOwnerAccess())
            <a href="{{ route('appointments.create') }}"
               style="background:var(--fg);color:var(--bg);padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                Book Your First Inspection
            </a>
            @endif
        </div>
        @endforelse

        {{ $appointments->links() }}
    </div>
</x-portal-layout>
