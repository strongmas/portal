<x-layouts.admin title="Property Values">

<div class="flex items-center justify-between mb-6">
    <div>
        <div class="eyebrow" style="margin-bottom:4px;">Admin</div>
        <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Property Value Tracker</h1>
    </div>
</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start;">

    {{-- Record new value --}}
    <div class="card" style="padding:24px;">
        <h2 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:16px;">Record New Value</h2>
        <form method="POST" action="{{ route('admin.property-values.store') }}" style="display:flex;flex-direction:column;gap:14px;">
            @csrf
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Property Unit <span style="color:var(--danger);">*</span></label>
                <select name="property_unit_id" required class="input-base" style="width:100%;">
                    <option value="">— select unit —</option>
                    @foreach($properties as $property)
                    <optgroup label="{{ $property->development_name }}">
                        @foreach($property->units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->label }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Value (₦) <span style="color:var(--danger);">*</span></label>
                <input type="text" name="value" value="{{ old('value') }}" required placeholder="e.g. 45,000,000" class="input-base" style="width:100%;">
            </div>
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Date Recorded <span style="color:var(--danger);">*</span></label>
                <input type="date" name="recorded_at" value="{{ old('recorded_at', now()->format('Y-m-d')) }}" required class="input-base" style="width:100%;">
            </div>
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Q1 2026 valuation" class="input-base" style="width:100%;">
            </div>
            <button type="submit" class="btn-primary">Save Entry</button>
        </form>
    </div>

    {{-- Value history --}}
    <div>
        @forelse($properties as $property)
        @php $unitsWithHistory = $property->units->filter(fn($u) => $u->valueHistories->count()); @endphp
        @if($unitsWithHistory->count())
        <div style="margin-bottom:24px;">
            <p class="eyebrow" style="margin-bottom:10px;">{{ $property->development_name }}</p>
            @foreach($unitsWithHistory as $unit)
            <div class="card" style="padding:0;overflow:hidden;margin-bottom:12px;">
                <div style="padding:12px 20px;background:var(--bg-raised);border-bottom:1px solid var(--line);">
                    <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0;">{{ $unit->label }}</p>
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <tbody>
                        @foreach($unit->valueHistories->sortByDesc('recorded_at') as $entry)
                        <tr style="border-top:1px solid var(--line);" onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                            <td style="padding:10px 20px;color:var(--fg-dim);">{{ $entry->recorded_at->format('d M Y') }}</td>
                            <td style="padding:10px 20px;font-weight:700;color:var(--fg);font-family:'Geist Mono',monospace;"><x-naira :kobo="$entry->value_kobo" /></td>
                            <td style="padding:10px 20px;color:var(--fg-mute);">{{ $entry->notes ?? '—' }}</td>
                            <td style="padding:10px 20px;text-align:right;">
                                <form method="POST" action="{{ route('admin.property-values.destroy', $entry) }}" onsubmit="return confirm('Delete this entry?')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:12px;color:var(--danger);background:none;border:none;cursor:pointer;text-decoration:underline;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
        @endif
        @empty
        <div class="card" style="padding:60px;text-align:center;color:var(--fg-dim);font-size:13px;">No value history recorded yet.</div>
        @endforelse
    </div>

</div>

</x-layouts.admin>
