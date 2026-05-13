<x-layouts.admin title="Sales Reps">
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="eyebrow" style="margin-bottom:4px;">Admin</div>
            <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Sales Representatives</h1>
        </div>
    </div>

    <div class="card" style="padding:24px;margin-bottom:20px;">
        <h2 style="font-size:14px;font-weight:600;color:var(--fg);margin-bottom:16px;">Add New Sales Rep</h2>
        <form method="POST" action="{{ route('admin.sales-reps.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
                <input type="text" name="name" placeholder="Full name" value="{{ old('name') }}" required class="input-base" style="width:100%;">
                <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required class="input-base" style="width:100%;">
                <input type="text" name="phone" placeholder="Phone" value="{{ old('phone') }}" class="input-base" style="width:100%;">
            </div>
            @error('email')<p style="color:var(--danger);font-size:11px;margin-bottom:8px;">{{ $message }}</p>@enderror
            <button type="submit" class="btn-primary">Add Rep</button>
        </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid var(--line);background:var(--bg-raised);">
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Name</th>
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Email</th>
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Phone</th>
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Buyers</th>
                    <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Status</th>
                    <th style="padding:10px 20px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reps as $rep)
                <tr style="border-top:1px solid var(--line);" onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:12px 20px;color:var(--fg);font-weight:500;">{{ $rep->name }}</td>
                    <td style="padding:12px 20px;color:var(--fg-dim);">{{ $rep->email }}</td>
                    <td style="padding:12px 20px;color:var(--fg-dim);">{{ $rep->phone ?? '—' }}</td>
                    <td style="padding:12px 20px;color:var(--fg-dim);">{{ $rep->buyers_count }}</td>
                    <td style="padding:12px 20px;">
                        @if($rep->is_active)
                            <span class="chip chip-ok">Active</span>
                        @else
                            <span class="chip chip-dark">Inactive</span>
                        @endif
                    </td>
                    <td style="padding:12px 20px;text-align:right;">
                        <form method="POST" action="{{ route('admin.sales-reps.destroy', $rep) }}"
                              onsubmit="return confirm('Delete {{ addslashes($rep->name) }}? Their buyers will be unassigned.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-ghost"
                                    style="font-size:11px;padding:4px 10px;color:var(--danger);border-color:rgba(244,114,114,0.3);">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div style="padding:60px;text-align:center;color:var(--fg-dim);font-size:13px;">No sales reps yet.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
