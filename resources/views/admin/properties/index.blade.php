<x-layouts.admin title="Properties">

<div class="page-header mb-6">
    <div>
        <div class="eyebrow" style="margin-bottom:6px;">Portfolio</div>
        <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Properties</h1>
        <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">{{ $properties->count() }} {{ Str::plural('development', $properties->count()) }} on record</p>
    </div>
    <a href="{{ route('admin.properties.create') }}" class="btn-primary">+ Add development</a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--line);">
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Code</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Development</th>
                <th style="padding:12px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Address</th>
                <th style="padding:12px 20px;text-align:center;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Units</th>
                <th style="padding:12px 20px;text-align:center;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Buyers</th>
                <th style="padding:12px 20px;text-align:right;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Collected</th>
                <th style="padding:12px 20px;text-align:right;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($properties as $property)
                <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                    onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 20px;font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">{{ $property->code }}</td>
                    <td style="padding:14px 20px;">
                        <a href="{{ route('admin.properties.show', $property) }}"
                           style="font-weight:500;color:var(--fg);text-decoration:none;display:block;"
                           onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">{{ $property->development_name }}</a>
                        @if($property->units->count())
                            <div style="font-size:11px;color:var(--fg-dim);margin-top:2px;">{{ $property->units->count() }} unit {{ Str::plural('type', $property->units->count()) }}</div>
                        @endif
                    </td>
                    <td style="padding:14px 20px;color:var(--fg-mute);font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $property->address }}</td>
                    <td style="padding:14px 20px;text-align:center;">
                        @if($property->units->count())
                            <span class="chip chip-dark" style="font-size:11px;padding:2px 8px;">{{ $property->units->count() }}</span>
                        @else
                            <span style="color:var(--fg-dim);font-size:12px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 20px;text-align:center;font-weight:600;color:var(--fg);">{{ $property->ownerships_count }}</td>
                    <td style="padding:14px 20px;text-align:right;font-family:'Geist Mono',monospace;font-weight:600;color:var(--ok);">{{ naira($property->total_collected_kobo) }}</td>
                    <td style="padding:14px 20px;text-align:right;">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.properties.show', $property) }}" style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">view</a>
                            <a href="{{ route('admin.properties.edit', $property) }}" style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">edit</a>
                            <a href="{{ route('admin.ownerships.create', ['property_id' => $property->id]) }}" style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">+ buyer</a>
                            @if($property->ownerships_count === 0)
                            <form method="POST" action="{{ route('admin.properties.destroy', $property) }}"
                                  onsubmit="return confirm('Permanently delete {{ addslashes($property->development_name) }}? This cannot be undone.')"
                                  style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" style="font-size:12px;color:var(--danger);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;">delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:60px;text-align:center;color:var(--fg-dim);font-size:14px;">
                        No developments yet.
                        <a href="{{ route('admin.properties.create') }}" style="color:var(--tan);text-decoration:underline;text-underline-offset:3px;margin-left:4px;">Add the first →</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

</x-layouts.admin>
