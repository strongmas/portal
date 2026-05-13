<x-layouts.admin title="Edit Insight">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.market-insights.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Market Insights</a>
    <span>/</span>
    <span style="color:var(--fg);">Edit</span>
</div>

<div style="max-width:640px;">
    <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:24px;">Edit Market Insight</h1>

    @if($errors->any())
        <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;">
            <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:2px;">
                @foreach($errors->all() as $e)<li style="font-size:13px;color:var(--danger);">{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.market-insights.update', $marketInsight) }}" class="card" style="padding:0;overflow:hidden;">
        @csrf @method('PATCH')
        <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Title <span style="color:var(--danger);">*</span></label>
                <input type="text" name="title" value="{{ old('title', $marketInsight->title) }}" required class="input-base" style="width:100%;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Region <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="region" value="{{ old('region', $marketInsight->region) }}" required class="input-base" style="width:100%;">
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Category <span style="color:var(--danger);">*</span></label>
                    <select name="category" class="input-base" style="width:100%;">
                        @foreach(['pricing' => 'Pricing', 'demand' => 'Demand', 'supply' => 'Supply', 'rental' => 'Rental', 'general' => 'General'] as $val => $label)
                        <option value="{{ $val }}" {{ old('category', $marketInsight->category) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Description <span style="color:var(--danger);">*</span></label>
                <textarea name="description" rows="5" required class="input-base" style="width:100%;resize:vertical;">{{ old('description', $marketInsight->description) }}</textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Key Value</label>
                    <input type="text" name="value" value="{{ old('value', $marketInsight->value) }}" class="input-base" style="width:100%;">
                </div>
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Period</label>
                    <input type="text" name="period" value="{{ old('period', $marketInsight->period) }}" class="input-base" style="width:100%;">
                </div>
            </div>
            <div>
                <label class="eyebrow" style="display:block;margin-bottom:6px;">Restrict to Property (optional)</label>
                <select name="property_id" class="input-base" style="width:100%;">
                    <option value="">— All buyers (global insight) —</option>
                    @foreach($properties as $property)
                    <option value="{{ $property->id }}" {{ old('property_id', $marketInsight->property_id) == $property->id ? 'selected' : '' }}>
                        {{ $property->development_name }}
                    </option>
                    @endforeach
                </select>
                <p style="font-size:11px;color:var(--fg-mute);margin-top:4px;">Leave blank to show to all buyers. Select a property to show only to buyers of that development.</p>
            </div>
            {{-- Price Movement --}}
            <div style="border-top:1px solid var(--line);padding-top:16px;">
                <div class="eyebrow" style="margin-bottom:12px;color:var(--fg-dim);">Price Movement (optional)</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Direction</label>
                        <select name="change_type" class="input-base" style="width:100%;">
                            <option value="">— No movement data —</option>
                            <option value="increase" {{ old('change_type', $marketInsight->change_type) === 'increase' ? 'selected' : '' }}>↑ Increased</option>
                            <option value="decrease" {{ old('change_type', $marketInsight->change_type) === 'decrease' ? 'selected' : '' }}>↓ Decreased</option>
                            <option value="stable"   {{ old('change_type', $marketInsight->change_type) === 'stable'   ? 'selected' : '' }}>→ Stable</option>
                        </select>
                        <p style="font-size:11px;color:var(--fg-mute);margin-top:4px;">Whether the property price has gone up or down.</p>
                    </div>
                    <div>
                        <label class="eyebrow" style="display:block;margin-bottom:6px;">Change % (optional)</label>
                        <input type="number" name="change_percent" value="{{ old('change_percent', $marketInsight->change_percent) }}"
                               step="0.01" min="0" max="999" placeholder="e.g. 12.5"
                               class="input-base" style="width:100%;">
                        <p style="font-size:11px;color:var(--fg-mute);margin-top:4px;">Percentage change, shown to buyers.</p>
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" id="is_published" name="is_published" value="1"
                       {{ old('is_published', $marketInsight->is_published) ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--hi);">
                <label for="is_published" style="font-size:14px;color:var(--fg);cursor:pointer;">Published (visible to buyers)</label>
            </div>
        </div>
        <div class="form-footer" style="padding:16px 24px;border-top:1px solid var(--line);background:var(--bg-raised);">
            <a href="{{ route('admin.market-insights.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;">Cancel</a>
            <button type="submit" class="btn-primary">Update Insight</button>
        </div>
    </form>
</div>

</x-layouts.admin>
