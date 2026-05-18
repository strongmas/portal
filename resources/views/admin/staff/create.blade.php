<x-layouts.admin title="Add Staff">
    <div style="max-width:560px;">
        <a href="{{ route('admin.staff.index') }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:20px;">← Back</a>
        <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin-bottom:24px;">Add Staff Member</h1>

        <div class="card" style="padding:24px;">
            <form method="POST" action="{{ route('admin.staff.store') }}">
                @csrf

                <div style="margin-bottom:16px;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Full Name <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="input-base" style="width:100%;">
                    @error('name')<p style="color:var(--danger);font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                <div style="margin-bottom:16px;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Email Address <span style="color:var(--danger);">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="input-base" style="width:100%;">
                    @error('email')<p style="color:var(--danger);font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                <div style="margin-bottom:24px;">
                    <label class="eyebrow" style="display:block;margin-bottom:6px;">Role <span style="color:var(--danger);">*</span></label>
                    @if($roles->isEmpty())
                        <p style="font-size:13px;color:var(--danger);">No roles yet. <a href="{{ route('admin.roles.create') }}" style="color:var(--hi);">Create a role first →</a></p>
                    @else
                        <select name="admin_role_id" required class="input-base" style="width:100%;">
                            <option value="">— Select role —</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('admin_role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('admin_role_id')<p style="color:var(--danger);font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                <div style="padding-top:20px;border-top:1px solid var(--line);">
                    <button type="submit" class="btn-primary" {{ $roles->isEmpty() ? 'disabled' : '' }}>
                        Create Account &amp; Send Credentials
                    </button>
                </div>
            </form>
        </div>

        <p style="font-size:12px;color:var(--fg-dim);margin-top:12px;">
            A random password will be generated and emailed to the staff member. They should change it on first login.
        </p>
    </div>
</x-layouts.admin>
