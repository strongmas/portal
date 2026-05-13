<x-layouts.admin title="Edit · {{ $property->development_name }}">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.properties.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Properties</a>
    <span>/</span>
    <a href="{{ route('admin.properties.show', $property) }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">{{ $property->development_name }}</a>
    <span>/</span>
    <span style="color:var(--fg);">Edit</span>
</div>

<div style="max-width:640px;">
    <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:24px;">Edit development</h1>

    @if($errors->any())
        <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--danger);">
            <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:2px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.properties.update', $property) }}" class="card" style="padding:0;overflow:hidden;">
        @csrf @method('PATCH')

        <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Development name</label>
                <input type="text" name="development_name" value="{{ old('development_name', $property->development_name) }}" required
                       class="input-base" style="width:100%;"/>
            </div>
            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Property code</label>
                <input type="text" name="code" value="{{ old('code', $property->code) }}" required
                       class="input-base" style="width:100%;font-family:'Geist Mono',monospace;font-size:12px;"/>
            </div>
            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Full address</label>
                <textarea name="address" required rows="2"
                          class="input-base" style="width:100%;resize:none;">{{ old('address', $property->address) }}</textarea>
            </div>
            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Description (shown to buyers)</label>
                <textarea name="description" rows="4"
                          class="input-base" style="width:100%;resize:vertical;">{{ old('description', $property->description) }}</textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Latitude</label>
                    <input type="number" name="lat" step="0.0000001" value="{{ old('lat', $property->lat) }}"
                           class="input-base" style="width:100%;" placeholder="e.g. 6.5244"/>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Longitude</label>
                    <input type="number" name="lng" step="0.0000001" value="{{ old('lng', $property->lng) }}"
                           class="input-base" style="width:100%;" placeholder="e.g. 3.3792"/>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:center;">
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Project status</label>
                    <select name="project_status" class="input-base" style="width:100%;">
                        @foreach(['planning' => 'Planning', 'foundation' => 'Foundation', 'construction' => 'Construction', 'finishing' => 'Finishing', 'completed' => 'Completed'] as $val => $label)
                            <option value="{{ $val }}" {{ old('project_status', $property->project_status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;align-items:center;gap:10px;padding-top:22px;">
                    <input type="hidden" name="is_off_plan" value="0">
                    <input type="checkbox" name="is_off_plan" id="is_off_plan" value="1"
                           {{ old('is_off_plan', $property->is_off_plan) ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--hi);">
                    <label for="is_off_plan" style="font-size:14px;color:var(--fg);cursor:pointer;">Off-plan property</label>
                </div>
            </div>
        </div>

        <div class="form-footer" style="padding:16px 24px;border-top:1px solid var(--line);background:var(--bg-raised);">
            <a href="{{ route('admin.properties.show', $property) }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">Cancel</a>
            <button type="submit" class="btn-primary" style="font-size:13px;">Save changes</button>
        </div>
    </form>
</div>

</x-layouts.admin>
