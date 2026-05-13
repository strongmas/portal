<x-portal-layout title="Market Insights">
    <div>
        <div class="mb-6">
            <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">Market Insights</h1>
            <p style="color:var(--fg-dim);font-size:14px;margin:0;">Real estate trends and market analysis</p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
            @forelse($insights as $insight)
            @php
                $colors=['pricing'=>['#3b82f6','#eff6ff'],'demand'=>['#22c55e','#f0fdf4'],'supply'=>['#f97316','#fff7ed'],'rental'=>['#a855f7','#faf5ff'],'general'=>['#6b7280','#f9fafb']];
                [$tc,$bg] = $colors[$insight->category] ?? $colors['general'];
            @endphp
            <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:14px;padding:22px;display:flex;flex-direction:column;">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <span style="font-size:11px;font-weight:700;color:{{ $tc }};background:{{ $tc }}22;padding:3px 10px;border-radius:99px;text-transform:uppercase;letter-spacing:.05em;">{{ $insight->category_label }}</span>
                    @if($insight->period)<span style="font-size:11px;color:var(--fg-mute);">{{ $insight->period }}</span>@endif
                </div>
                <h3 style="font-size:16px;font-weight:700;color:var(--fg);margin:0 0 8px;line-height:1.3;">{{ $insight->title }}</h3>
                @if($insight->value)<p style="font-size:22px;font-weight:800;color:{{ $tc }};margin:0 0 10px;">{{ $insight->value }}</p>@endif
                <p style="font-size:14px;color:var(--fg-dim);margin:0;line-height:1.6;flex:1;">{{ Str::limit($insight->description, 180) }}</p>
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--line);">
                    <p style="font-size:12px;color:var(--fg-mute);margin:0;">{{ $insight->region }} · {{ $insight->created_at->format('d M Y') }}</p>
                </div>
            </div>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--fg-dim);">
                <p>No market insights published yet.</p>
            </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $insights->links() }}</div>
    </div>
</x-portal-layout>
