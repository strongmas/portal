<x-layouts.admin title="Add buyer">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.buyers.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Buyers</a>
    <span>/</span>
    <span style="color:var(--fg);">Add buyer</span>
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

<div style="max-width:680px;"
     x-data="{
        propertyId: '{{ old('property_id') }}',
        unitId: '{{ old('property_unit_id') }}',
        paymentPlan: '{{ old('payment_plan', 'instalment') }}',
        instalmentFrequency: '{{ old('instalment_frequency', 'monthly') }}',
        allUnits: {{ $unitsMap }},
        get units() { return this.allUnits[this.propertyId] || []; },
        get isInstalment() { return this.paymentPlan === 'instalment'; },
        onPropertyChange() { this.unitId = ''; },
     }">

    <div class="eyebrow" style="margin-bottom:6px;">People</div>
    <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:4px;">Add new buyer</h1>
    <p style="font-size:13px;color:var(--fg-mute);margin-bottom:24px;">Creates their account, links them to a property, and sends a welcome email with login details.</p>

    @if($errors->any())
        <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:var(--r-lg);padding:14px 18px;margin-bottom:20px;">
            <ul style="list-style:disc;padding-left:16px;display:flex;flex-direction:column;gap:4px;">
                @foreach($errors->all() as $error)
                    <li style="font-size:13px;color:var(--danger);">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.buyers.store') }}" style="display:flex;flex-direction:column;gap:0;">
        @csrf

        {{-- Personal details --}}
        <div class="card" style="padding:24px;border-radius:var(--r-lg) var(--r-lg) 0 0;border-bottom:none;">
            <h2 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:16px;">Personal details</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div style="grid-column:span 2;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Full name <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="input-base"/>
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Email <span style="color:var(--danger);">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="input-base"/>
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="input-base"/>
                </div>
            </div>
        </div>

        {{-- Sales Rep --}}
        <div class="card" style="padding:24px;border-radius:0;border-top:none;border-bottom:none;">
            <h2 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:16px;">Sales representative</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div style="grid-column:span 2;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Assigned sales rep</label>
                    <select name="sales_rep_id" class="input-base">
                        <option value="">— none / unassigned —</option>
                        @foreach($salesReps as $rep)
                            <option value="{{ $rep->id }}" {{ old('sales_rep_id') == $rep->id ? 'selected' : '' }}>
                                {{ $rep->name }}{{ $rep->phone ? ' · ' . $rep->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p style="font-size:12px;color:var(--fg-dim);margin-top:4px;">The sales rep who brought in this client. Appointment notifications will be sent to them.</p>
                </div>
            </div>
        </div>

        {{-- Property + unit --}}
        <div class="card" style="padding:24px;border-radius:0;border-top:none;border-bottom:none;">
            <h2 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:16px;">Property &amp; unit</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Development <span style="color:var(--danger);">*</span></label>
                    <select name="property_id" x-model="propertyId" @change="onPropertyChange()" required class="input-base">
                        <option value="">— select development —</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->development_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Unit type</label>
                    <select name="property_unit_id" x-model="unitId" class="input-base" :disabled="!propertyId || units.length === 0">
                        <option value="">— select unit —</option>
                        <template x-for="u in units" :key="u.id">
                            <option :value="u.id" x-text="u.label"></option>
                        </template>
                    </select>
                    <p style="font-size:12px;color:var(--fg-dim);margin-top:4px;" x-show="propertyId && units.length === 0">No unit types added to this development yet.</p>
                </div>
            </div>
        </div>

        {{-- Deal terms --}}
        <div class="card" style="padding:24px;border-radius:0;border-top:none;border-bottom:none;">
            <h2 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:16px;">Deal terms</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Agreed price (₦) <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="agreed_price" value="{{ old('agreed_price') }}" required placeholder="12,500,000" class="input-base"/>
                    <p style="font-size:12px;color:var(--fg-dim);margin-top:4px;">Negotiated price — buyer sees this</p>
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Payment plan <span style="color:var(--danger);">*</span></label>
                    <select name="payment_plan" required x-model="paymentPlan" class="input-base">
                        <option value="instalment">Instalment</option>
                        <option value="outright">Outright</option>
                    </select>
                </div>
                <div x-show="isInstalment" x-cloak>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Instalment frequency <span style="color:var(--danger);">*</span></label>
                    <select name="instalment_frequency" x-model="instalmentFrequency" :required="isInstalment" class="input-base">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly (every 3 months)</option>
                    </select>
                </div>
                <div x-show="isInstalment" x-cloak>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Total instalments <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="total_instalments" value="{{ old('total_instalments', 12) }}"
                           :required="isInstalment" min="1" max="120" class="input-base"/>
                    <p style="font-size:12px;color:var(--fg-dim);margin-top:4px;" x-text="instalmentFrequency === 'quarterly' ? 'Schedule auto-generated on quarterly intervals' : 'Schedule auto-generated on monthly intervals'"></p>
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Start date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" class="input-base"/>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card form-footer" style="padding:16px 24px;border-radius:0 0 var(--r-lg) var(--r-lg);border-top:none;background:var(--bg-raised);">
            <a href="{{ route('admin.buyers.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">Cancel</a>
            <button type="submit" class="btn-primary">Create buyer &amp; send welcome email →</button>
        </div>
    </form>
</div>

</x-layouts.admin>
