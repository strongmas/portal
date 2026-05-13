<x-layouts.admin title="Add development">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.properties.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Properties</a>
    <span>/</span>
    <span style="color:var(--fg);">Add development</span>
</div>

<div style="max-width:640px;">
    <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:4px;">Add new development</h1>
    <p style="font-size:13px;color:var(--fg-mute);margin-bottom:24px;">Create the development first, then add unit types (2BR Terrace, 3BR Maisonette, etc.) from the detail page.</p>

    @if($errors->any())
        <div style="background:var(--danger-bg);border:1px solid rgba(244,114,114,0.3);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--danger);">
            <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:2px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.properties.store') }}" class="card" style="padding:0;overflow:hidden;">
        @csrf

        <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
            <div class="grid gap-4" style="grid-template-columns:1fr 1fr;">
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Development name <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="development_name" value="{{ old('development_name') }}" required
                           placeholder="e.g. Orion Towers, Elysian Rise"
                           class="input-base" style="width:100%;"/>
                </div>
                <div>
                    <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Property code <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                           placeholder="e.g. STR-ORN-001"
                           class="input-base" style="width:100%;font-family:'Geist Mono',monospace;font-size:12px;"/>
                    <p style="font-size:11px;color:var(--fg-dim);margin-top:4px;">Unique ID — will be uppercased</p>
                </div>
            </div>

            <div>
                <label style="display:block;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);margin-bottom:6px;">Full address <span style="color:var(--danger);">*</span></label>
                <textarea name="address" required rows="2"
                          class="input-base" style="width:100%;resize:none;"
                          placeholder="Plot number, street, estate, LGA, state">{{ old('address') }}</textarea>
            </div>
        </div>

        <div class="form-footer" style="padding:16px 24px;border-top:1px solid var(--line);background:var(--bg-raised);">
            <a href="{{ route('admin.properties.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">Cancel</a>
            <div class="flex items-center gap-3">
                <button type="submit" name="next" value="link" class="btn-ghost" style="font-size:13px;">
                    Save &amp; link a buyer →
                </button>
                <button type="submit" class="btn-primary" style="font-size:13px;">
                    Save &amp; add unit types →
                </button>
            </div>
        </div>
    </form>
</div>

</x-layouts.admin>
