<x-layouts.admin title="Market Insights">

<div class="flex items-center justify-between mb-6">
    <div>
        <div class="eyebrow" style="margin-bottom:4px;">Content</div>
        <h1 style="font-size:24px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Market Insights</h1>
    </div>
    <a href="{{ route('admin.market-insights.create') }}" class="btn-primary">+ New Insight</a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--line);background:var(--bg-raised);">
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Title</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Category</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Price Movement</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Region</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Period</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Status</th>
                <th style="padding:10px 20px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($insights as $insight)
            <tr style="border-top:1px solid var(--line);" onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                <td style="padding:12px 20px;font-weight:500;color:var(--fg);">{{ Str::limit($insight->title, 50) }}</td>
                <td style="padding:12px 20px;color:var(--fg-dim);text-transform:capitalize;">{{ $insight->category }}</td>
                <td style="padding:12px 20px;">
                    @if($insight->change_type)
                        @php
                            $clr = match($insight->change_type) {
                                'increase' => 'var(--ok)',
                                'decrease' => 'var(--danger)',
                                default    => 'var(--fg-dim)',
                            };
                        @endphp
                        <span style="font-size:13px;font-weight:600;color:{{ $clr }};">
                            {{ $insight->change_arrow }}
                            @if($insight->change_percent) {{ number_format($insight->change_percent, 2) }}% @endif
                        </span>
                    @else
                        <span style="color:var(--fg-faint);font-size:12px;">—</span>
                    @endif
                </td>
                <td style="padding:12px 20px;color:var(--fg-dim);">{{ $insight->region }}</td>
                <td style="padding:12px 20px;color:var(--fg-mute);">{{ $insight->period ?? '—' }}</td>
                <td style="padding:12px 20px;">
                    @if($insight->is_published)
                        <span class="chip chip-ok" style="font-size:10px;padding:2px 8px;">Live</span>
                    @else
                        <span class="chip chip-pending" style="font-size:10px;padding:2px 8px;">Draft</span>
                    @endif
                </td>
                <td style="padding:12px 20px;text-align:right;">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.market-insights.edit', $insight) }}" style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">Edit</a>
                        <form method="POST" action="{{ route('admin.market-insights.destroy', $insight) }}" onsubmit="return confirm('Delete this insight?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:12px;color:var(--danger);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div style="padding:60px;text-align:center;color:var(--fg-dim);font-size:13px;">No market insights yet. <a href="{{ route('admin.market-insights.create') }}" style="color:var(--tan);text-decoration:underline;">Add one →</a></div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $insights->links() }}</div>

</x-layouts.admin>
