<x-layouts.admin title="Edit Role">
    <div style="max-width:560px;">
        <a href="{{ route('admin.roles.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:20px;">← Back</a>
        <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:24px;">Edit Role — {{ $role->name }}</h1>

        <div class="card" style="padding:24px;">
            <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                @csrf @method('PATCH')

                <div style="margin-bottom:20px;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Role Name <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}"
                           required class="input-base" style="width:100%;">
                    @error('name')<p style="color:var(--danger);font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                <div style="margin-bottom:24px;">
                    <label class="eyebrow" style="display:block;margin-bottom:10px;">Permissions</label>
                    @php $allPermissions = \App\Models\AdminRole::ALL_PERMISSIONS; $current = old('permissions', $role->permissions ?? []); @endphp
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        @foreach($allPermissions as $key => $label)
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 14px;border:1px solid var(--line);border-radius:8px;transition:border-color .15s;"
                               onmouseover="this.style.borderColor='var(--hi)'" onmouseout="this.style.borderColor='var(--line)'">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                   {{ in_array($key, $current) ? 'checked' : '' }}
                                   style="width:16px;height:16px;accent-color:var(--hi);">
                            <span style="font-size:13px;font-weight:600;color:var(--fg);">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div style="padding-top:20px;border-top:1px solid var(--line);">
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
