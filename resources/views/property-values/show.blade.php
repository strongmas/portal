<x-portal-layout title="Value Trends">
    <div style="max-width:720px;">
        <a href="{{ route('properties.show', $ownership) }}" style="font-size:13px;color:var(--fg-dim);text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:20px;">← Back to Property</a>
        <div class="mb-6">
            <h1 style="font-size:22px;font-weight:700;color:var(--fg);margin:0 0 4px;">Value Trends</h1>
            <p style="color:var(--fg-dim);font-size:14px;margin:0;">{{ $ownership->display_name }}</p>
        </div>

        @if($histories->count())
        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:16px;padding:24px;margin-bottom:20px;">
            <canvas id="valueChart" height="260"></canvas>
        </div>

        <div style="background:var(--bg-card);border:1px solid var(--line);border-radius:14px;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--line);">
                <p style="font-size:13px;font-weight:700;color:var(--fg);margin:0;">Value History</p>
            </div>
            @foreach($histories->sortByDesc('recorded_at') as $entry)
            <div style="padding:14px 20px;border-bottom:1px solid var(--line);display:flex;justify-between;align-items:center;">
                <div>
                    <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 2px;"><x-naira :kobo="$entry->value_kobo" /></p>
                    @if($entry->notes)<p style="font-size:12px;color:var(--fg-dim);margin:0;">{{ $entry->notes }}</p>@endif
                </div>
                <p style="font-size:13px;color:var(--fg-dim);margin:0;">{{ $entry->recorded_at->format('d M Y') }}</p>
            </div>
            @endforeach
        </div>

        @php
            $chartLabels = $histories->pluck('recorded_at')->map->format('M Y')->values();
            $chartData   = $histories->pluck('value_kobo')->map(fn($v) => $v / 100)->values();
        @endphp
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Chart(document.getElementById('valueChart'), {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Value (₦)',
                        data: @json($chartData),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.08)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 5,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { ticks: { callback: v => '₦' + new Intl.NumberFormat('en-NG').format(v) } }
                    }
                }
            });
        });
        </script>
        @else
        <div style="text-align:center;padding:60px;color:var(--fg-dim);">
            <p>No value trend data available for this unit yet.</p>
        </div>
        @endif
    </div>
</x-portal-layout>
