<x-layouts.admin title="Appointment">
    <div style="max-width:680px;">
        <a href="{{ route('admin.appointments.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:20px;">← Back</a>

        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="eyebrow" style="margin-bottom:4px;">Appointment</div>
                <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">{{ $appointment->property->development_name }}</h1>
            </div>
        </div>

        <div class="card" style="padding:24px;margin-bottom:20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
                <div>
                    <div class="eyebrow" style="margin-bottom:4px;">Buyer</div>
                    <p style="font-weight:600;color:var(--fg);font-size:14px;">{{ $appointment->user->name }}</p>
                </div>
                <div>
                    <div class="eyebrow" style="margin-bottom:4px;">Phone</div>
                    <p style="font-weight:600;color:var(--fg);font-size:14px;">{{ $appointment->user->phone ?? '—' }}</p>
                </div>
                <div>
                    <div class="eyebrow" style="margin-bottom:4px;">Email</div>
                    <p style="font-weight:600;color:var(--fg);font-size:14px;">{{ $appointment->user->email }}</p>
                </div>
                <div>
                    <div class="eyebrow" style="margin-bottom:4px;">Date & Time</div>
                    <p style="font-weight:600;color:var(--fg);font-size:14px;">{{ $appointment->date_time }}</p>
                </div>
                @if($appointment->notes)
                <div style="grid-column:1 / -1;">
                    <div class="eyebrow" style="margin-bottom:4px;">Notes</div>
                    <p style="color:var(--fg-dim);font-size:13px;">{{ $appointment->notes }}</p>
                </div>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}" style="border-top:1px solid var(--line);padding-top:24px;">
                @csrf @method('PATCH')
                <h2 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:16px;">Update Appointment</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Status</label>
                        <select name="status" class="input-base" style="width:100%;">
                            @foreach(['pending','confirmed','inspected','cancelled','rescheduled'] as $s)
                            <option value="{{ $s }}" {{ $appointment->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Assigned Sales Rep</label>
                        <select name="sales_rep_id" class="input-base" style="width:100%;">
                            <option value="">None</option>
                            @foreach($reps as $rep)
                            <option value="{{ $rep->id }}" {{ $appointment->sales_rep_id===$rep->id?'selected':'' }}>{{ $rep->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Inspection Outcome Notes</label>
                    <textarea name="outcome_notes" rows="3" class="input-base" style="width:100%;">{{ old('outcome_notes', $appointment->outcome_notes) }}</textarea>
                </div>
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
