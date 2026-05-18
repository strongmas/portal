<x-layouts.admin title="Link Buyer to Property">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.dashboard') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Dashboard</a>
    <span>/</span>
    <span style="color:var(--fg);">Link buyer to property</span>
</div>

@php
    $unitsMap = \Illuminate\Support\Js::from(
        $units->groupBy('property_id')->map(fn($group) => $group->map(fn($u) => [
            'id'    => $u->id,
            'label' => $u->label,
            'price' => $u->list_price_kobo,
        ]))->toArray()
    );
@endphp

<div style="max-width:640px;"
     x-data="{
        propertyId: '{{ old('property_id', $selectedProperty ?? '') }}',
        unitId: '{{ old('property_unit_id') }}',
        paymentPlan: '{{ old('payment_plan', 'instalment') }}',
        instalmentFrequency: '{{ old('instalment_frequency', 'monthly') }}',
        allUnits: {{ $unitsMap }},
        get units() { return this.allUnits[this.propertyId] || []; },
        get isInstalment() { return this.paymentPlan === 'instalment'; },
        onPropertyChange() { this.unitId = ''; },
     }">

    <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:4px;">Link buyer to property</h1>
    <p style="font-size:13px;color:var(--fg-mute);margin-bottom:24px;">This creates the ownership record. Tick <strong style="color:var(--fg);">Generate schedule</strong> to auto-build the instalment calendar.</p>

    @if($errors->any())
        <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--danger);">
            <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:2px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.ownerships.store') }}" class="card" style="padding:0;overflow:hidden;">
        @csrf

        {{-- Buyer + Property --}}
        <div style="padding:20px 24px;border-bottom:1px solid var(--line);">
            <h2 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:16px;">Select buyer &amp; property</h2>
            <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Buyer <span style="color:var(--danger);">*</span></label>
                    <select name="user_id" required class="input-base" style="width:100%;">
                        <option value="">— select buyer —</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}" {{ (old('user_id', $selectedBuyer) == $buyer->id) ? 'selected' : '' }}>
                                {{ $buyer->name }} ({{ $buyer->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Development <span style="color:var(--danger);">*</span></label>
                    <select name="property_id" x-model="propertyId" @change="onPropertyChange()" required class="input-base" style="width:100%;">
                        <option value="">— select development —</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ (old('property_id', $selectedProperty) == $property->id) ? 'selected' : '' }}>
                                {{ $property->development_name }} · {{ $property->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Unit type</label>
                    <select name="property_unit_id" x-model="unitId" class="input-base" style="width:100%;"
                            :disabled="!propertyId || units.length === 0">
                        <option value="">— select unit type (optional) —</option>
                        <template x-for="u in units" :key="u.id">
                            <option :value="u.id" x-text="u.label"></option>
                        </template>
                    </select>
                    <p style="font-size:11px;color:var(--fg-dim);margin-top:4px;" x-show="propertyId && units.length === 0">
                        No unit types for this development yet — <a href="{{ route('admin.properties.index') }}" style="color:var(--tan);text-decoration:underline;text-underline-offset:3px;">add them first</a>.
                    </p>
                </div>
            </div>
        </div>

        {{-- Deal terms --}}
        <div style="padding:20px 24px;border-bottom:1px solid var(--line);">
            <h2 style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:16px;">Deal terms</h2>
            <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Agreed price (₦) <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="agreed_price" value="{{ old('agreed_price') }}" required
                           placeholder="e.g. 32,500,000"
                           class="input-base" style="width:100%;"/>
                    <p style="font-size:11px;color:var(--fg-dim);margin-top:4px;">Negotiated — buyer sees this only</p>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Payment plan <span style="color:var(--danger);">*</span></label>
                    <select name="payment_plan" required x-model="paymentPlan" class="input-base" style="width:100%;">
                        <option value="instalment" {{ old('payment_plan') !== 'outright' ? 'selected' : '' }}>Instalment</option>
                        <option value="outright" {{ old('payment_plan') === 'outright' ? 'selected' : '' }}>Outright</option>
                    </select>
                </div>
                <div x-show="isInstalment" x-cloak>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Instalment frequency</label>
                    <select name="instalment_frequency" x-model="instalmentFrequency" class="input-base" style="width:100%;">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly (every 3 months)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Total instalments <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="total_instalments" value="{{ old('total_instalments', 24) }}" required
                           min="1" max="360" class="input-base" style="width:100%;"/>
                    <p style="font-size:11px;color:var(--fg-dim);margin-top:4px;" x-text="instalmentFrequency === 'quarterly' ? 'Number of quarterly payments' : 'Number of monthly payments'">Number of monthly payments</p>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Status</label>
                    <select name="status" class="input-base" style="width:100%;">
                        <option value="active" selected>Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Start date <span style="color:var(--danger);">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required
                           class="input-base" style="width:100%;"/>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Expected completion</label>
                    <input type="date" name="expected_completion_date" value="{{ old('expected_completion_date') }}"
                           class="input-base" style="width:100%;"/>
                </div>
            </div>
        </div>

        {{-- Instalment schedule --}}
        <div style="padding:20px 24px;border-bottom:1px solid var(--line);">
            <div class="flex items-start gap-3">
                <input type="checkbox" id="generate_schedule" name="generate_schedule" value="1"
                       checked style="margin-top:2px;width:16px;height:16px;accent-color:var(--tan);flex-shrink:0;cursor:pointer;"/>
                <div>
                    <label for="generate_schedule" style="font-size:13px;font-weight:500;color:var(--fg);cursor:pointer;">Auto-generate instalment schedule</label>
                    <p style="font-size:12px;color:var(--fg-dim);margin-top:3px;line-height:1.5;">
                        Creates one instalment row per month from the start date. The buyer's portal will immediately show a payment calendar.
                    </p>
                </div>
            </div>
        </div>

        <div class="form-footer" style="padding:16px 24px;background:var(--bg-raised);">
            <a href="{{ route('admin.buyers.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">Cancel</a>
            <button type="submit" class="btn-primary" style="font-size:13px;">
                Link &amp; generate →
            </button>
        </div>
    </form>
</div>

</x-layouts.admin>
