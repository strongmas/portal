<x-layouts.admin title="Edit link · {{ $ownership->property->development_name }}">

@php
    $unitsMap = \Illuminate\Support\Js::from(
        $units->groupBy('property_id')->map(fn($group) => $group->map(fn($u) => [
            'id'    => $u->id,
            'label' => $u->label,
            'price' => $u->list_price_kobo,
        ]))->toArray()
    );
@endphp

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.buyers.show', $ownership->user) }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">{{ $ownership->user->name }}</a>
    <span>/</span>
    <span style="color:var(--fg);">Edit ownership</span>
</div>

<div style="max-width:640px;"
     x-data="{
        propertyId: '{{ $ownership->property_id }}',
        unitId: '{{ old('property_unit_id', $ownership->property_unit_id ?? '') }}',
        allUnits: {{ $unitsMap }},
        get units() { return this.allUnits[this.propertyId] || []; },
     }">

    <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:4px;">Edit ownership</h1>
    <p style="font-size:13px;color:var(--fg-mute);margin-bottom:24px;">
        <strong style="color:var(--fg);">{{ $ownership->user->name }}</strong>
        <span style="color:var(--fg-dim);margin:0 6px;">→</span>
        <strong style="color:var(--fg);">{{ $ownership->property->development_name }}</strong>
    </p>

    @if($errors->any())
        <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--danger);">
            <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:2px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.ownerships.update', $ownership) }}" class="card" style="padding:0;overflow:hidden;">
        @csrf @method('PATCH')

        {{-- Unit type --}}
        <div style="padding:20px 24px;border-bottom:1px solid var(--line);">
            <h2 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:14px;">Unit type</h2>
            <select name="property_unit_id" x-model="unitId" class="input-base" style="width:100%;">
                <option value="">— no specific unit —</option>
                <template x-for="u in units" :key="u.id">
                    <option :value="u.id" :selected="u.id == unitId" x-text="u.label"></option>
                </template>
            </select>
        </div>

        {{-- Deal terms --}}
        <div style="padding:20px 24px;border-bottom:1px solid var(--line);">
            <h2 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:16px;">Deal terms</h2>
            <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Agreed price (₦)</label>
                    <input type="text" name="agreed_price" value="{{ old('agreed_price', number_format($ownership->agreed_price_kobo / 100, 0)) }}" required
                           class="input-base" style="width:100%;"/>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Payment plan</label>
                    <select name="payment_plan" required class="input-base" style="width:100%;">
                        <option value="instalment" {{ $ownership->payment_plan === 'instalment' ? 'selected' : '' }}>Instalment</option>
                        <option value="outright" {{ $ownership->payment_plan === 'outright' ? 'selected' : '' }}>Outright</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Instalment frequency</label>
                    <select name="instalment_frequency" class="input-base" style="width:100%;">
                        <option value="monthly" {{ ($ownership->instalment_frequency ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ ($ownership->instalment_frequency ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly (every 3 months)</option>
                        <option value="bi-annually" {{ ($ownership->instalment_frequency ?? '') === 'bi-annually' ? 'selected' : '' }}>Bi-annually (every 6 months)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Total instalments</label>
                    <input type="number" name="total_instalments" value="{{ old('total_instalments', $ownership->total_instalments) }}" required
                           min="1" max="360" class="input-base" style="width:100%;"/>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Status</label>
                    <select name="status" class="input-base" style="width:100%;">
                        <option value="active" {{ $ownership->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ $ownership->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $ownership->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Start date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $ownership->start_date?->format('Y-m-d')) }}" required
                           class="input-base" style="width:100%;"/>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Expected completion</label>
                    <input type="date" name="expected_completion_date" value="{{ old('expected_completion_date', $ownership->expected_completion_date?->format('Y-m-d')) }}"
                           class="input-base" style="width:100%;"/>
                </div>
            </div>
        </div>

        {{-- Regenerate schedule warning --}}
        <div style="padding:16px 24px;border-bottom:1px solid var(--line);background:var(--warn-bg);border-left:none;border-right:none;">
            <div class="flex items-start gap-3">
                <input type="checkbox" id="regenerate_schedule" name="regenerate_schedule" value="1"
                       style="margin-top:2px;width:16px;height:16px;accent-color:var(--warn);flex-shrink:0;cursor:pointer;"/>
                <div>
                    <label for="regenerate_schedule" style="font-size:13px;font-weight:500;color:var(--warn);cursor:pointer;">Regenerate instalment schedule</label>
                    <p style="font-size:12px;color:var(--fg-mute);margin-top:3px;line-height:1.5;">
                        Rebuilds the instalment dates from the new start date / total count. Payments already logged are kept and re-applied to the new schedule.
                    </p>
                </div>
            </div>
        </div>

        <div class="form-footer" style="padding:16px 24px;background:var(--bg-raised);">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.buyers.show', $ownership->user) }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">Cancel</a>
                {{-- Belongs to the separate form below the main one. A form nested
                     inside this one is dropped by the browser, which merged its
                     DELETE method into "Save changes" and deleted the ownership. --}}
                <button type="submit" form="remove-ownership-form" style="font-size:13px;color:var(--danger);background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;text-underline-offset:3px;"
                        onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">Remove link</button>
            </div>
            <button type="submit" class="btn-primary" style="font-size:13px;">Save changes</button>
        </div>
    </form>

    <form id="remove-ownership-form" method="POST" action="{{ route('admin.ownerships.destroy', $ownership) }}"
          onsubmit="return confirm('Remove this property link? This is only possible when it has no payments logged.')">
        @csrf @method('DELETE')
    </form>
</div>

</x-layouts.admin>
