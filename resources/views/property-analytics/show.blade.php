<x-portal-layout title="Investment Analytics">
@php
    $isAppreciated = $gainKobo >= 0;
    $gainColor     = $isAppreciated ? 'var(--ok)' : 'var(--danger)';
    $gainBg        = $isAppreciated ? 'var(--ok-bg)' : 'var(--danger-bg)';
    $gainBorder    = $isAppreciated ? 'rgba(93,217,167,0.24)' : 'rgba(244,114,114,0.24)';
    $gainArrow     = $isAppreciated ? '↑' : '↓';

    $purchaseKoboDisplay = $ownership->agreed_price_kobo;
    $purchaseNaira       = $purchaseKoboDisplay / 100;
    $currentNaira        = $currentKobo / 100;
    $gainNaira           = $gainKobo / 100;

    $chartLabels = $histories->pluck('recorded_at')->map->format('d M Y')->values();
    $chartValues = $histories->pluck('value_kobo')->map(fn($v) => $v / 100)->values();
    $purchaseLine = $histories->map(fn() => $purchaseNaira)->values();

    // Build history with deltas
    $prevKobo = null;
    $historyRows = $histories->sortByDesc('recorded_at')->map(function ($entry) use (&$prevKobo) {
        // prev is actually the entry before this in ascending order
        return $entry;
    });
    $ascRows = $histories->sortBy('recorded_at')->values();
@endphp

<div>
    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;font-size:13px;color:var(--fg-dim);">
        <a href="{{ route('properties.index') }}" style="color:var(--fg-dim);text-decoration:none;">Portfolio</a>
        <span>›</span>
        <a href="{{ route('properties.show', $ownership) }}" style="color:var(--fg-dim);text-decoration:none;">{{ $ownership->display_name }}</a>
        <span>›</span>
        <span style="color:var(--fg);">Analytics</span>
    </div>

    {{-- Page header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:32px;">
        <div>
            <div class="eyebrow eyebrow-tan" style="margin-bottom:6px;">Investment Analytics</div>
            <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;margin:0 0 6px;">{{ $ownership->display_name }}</h1>
            <p style="font-size:14px;color:var(--fg-mute);margin:0;">
                {{ $ownership->property->address }}
                @if($ownership->purchase_date)
                    · Purchased {{ $ownership->purchase_date->format('M Y') }}
                @endif
            </p>
        </div>
        <a href="{{ route('property-values.show', $ownership) }}" class="btn-ghost" style="font-size:13px;">View raw data →</a>
    </div>

    {{-- KPI Cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;">

        {{-- Purchase Price --}}
        <div class="card" style="padding:20px;">
            <div class="stat-label">Purchase Price</div>
            <div class="stat-value" style="font-size:22px;">₦{{ number_format($purchaseNaira) }}</div>
            @if($ownership->purchase_date)
                <div style="font-size:12px;color:var(--fg-dim);margin-top:6px;">{{ $ownership->purchase_date->format('d M Y') }}</div>
            @endif
        </div>

        {{-- Current Value --}}
        <div class="card" style="padding:20px;border-color:var(--line-tan);background:var(--info-bg);">
            <div class="stat-label" style="color:var(--tan);">Current Market Value</div>
            <div class="stat-value" style="font-size:22px;color:var(--tan);">₦{{ number_format($currentNaira) }}</div>
            @if($histories->count())
                <div style="font-size:12px;color:var(--fg-dim);margin-top:6px;">As of {{ $histories->last()->recorded_at->format('d M Y') }}</div>
            @else
                <div style="font-size:12px;color:var(--fg-dim);margin-top:6px;">Based on purchase price</div>
            @endif
        </div>

        {{-- Total Return --}}
        <div class="card" style="padding:20px;border-color:{{ $gainBorder }};background:{{ $gainBg }};">
            <div class="stat-label" style="color:{{ $gainColor }};">Total Return</div>
            <div class="stat-value" style="font-size:22px;color:{{ $gainColor }};">
                {{ $gainArrow }} ₦{{ number_format(abs($gainNaira)) }}
            </div>
            <div style="font-size:12px;color:{{ $gainColor }};margin-top:6px;font-weight:600;">
                {{ $gainArrow }} {{ abs($gainPercent) }}%
                @if($gainKobo == 0) <span style="opacity:0.6;">No change</span> @endif
            </div>
        </div>

        {{-- Annual Growth --}}
        <div class="card" style="padding:20px;">
            <div class="stat-label">Annual Growth (CAGR)</div>
            @if($cagr !== null)
                @php $cagrColor = $cagr >= 0 ? 'var(--ok)' : 'var(--danger)'; @endphp
                <div class="stat-value" style="font-size:22px;color:{{ $cagrColor }};">
                    {{ $cagr >= 0 ? '+' : '' }}{{ $cagr }}%
                </div>
                <div style="font-size:12px;color:var(--fg-dim);margin-top:6px;">
                    Over {{ round($years, 1) }} {{ $years == 1 ? 'year' : 'years' }}
                </div>
            @else
                <div class="stat-value" style="font-size:22px;color:var(--fg-faint);">—</div>
                <div style="font-size:12px;color:var(--fg-dim);margin-top:6px;">Insufficient data</div>
            @endif
        </div>
    </div>

    {{-- Chart --}}
    @if($histories->count() >= 2)
    <div class="card" style="padding:24px;margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div>
                <div class="eyebrow" style="margin-bottom:4px;">Value Over Time</div>
                <p style="font-size:13px;color:var(--fg-dim);margin:0;">Market value vs. your purchase price</p>
            </div>
            <div style="display:flex;align-items:center;gap:16px;font-size:12px;color:var(--fg-dim);">
                <span style="display:flex;align-items:center;gap:6px;">
                    <span style="width:16px;height:2px;background:var(--tan);border-radius:2px;display:inline-block;"></span>Market value
                </span>
                <span style="display:flex;align-items:center;gap:6px;">
                    <span style="width:16px;height:2px;background:var(--fg-faint);border-radius:2px;display:inline-block;border-top:2px dashed var(--fg-faint);"></span>Purchase price
                </span>
            </div>
        </div>
        <canvas id="analyticsChart" height="220"></canvas>
    </div>
    @elseif($histories->count() === 1)
    <div class="card" style="padding:24px;margin-bottom:24px;text-align:center;color:var(--fg-dim);">
        <p style="margin:0;font-size:14px;">Chart requires at least 2 value entries. One valuation recorded so far.</p>
    </div>
    @else
    <div class="card" style="padding:40px;margin-bottom:24px;text-align:center;">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--bg-raised);display:grid;place-items:center;margin:0 auto 16px;">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--fg-dim);"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <p style="font-size:15px;font-weight:600;color:var(--fg);margin:0 0 6px;">No valuation data yet</p>
        <p style="font-size:13px;color:var(--fg-dim);margin:0;">Value history will appear here once your property is appraised by Strongmas.</p>
    </div>
    @endif

    {{-- Payment Progress --}}
    <div class="card" style="padding:24px;margin-bottom:24px;">
        <div class="eyebrow" style="margin-bottom:16px;">Payment Progress</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:20px;margin-bottom:20px;">
            <div>
                <div style="font-size:12px;color:var(--fg-dim);margin-bottom:4px;">Agreed Price</div>
                <div style="font-size:18px;font-weight:600;font-family:'Geist Mono',monospace;color:var(--fg);">₦{{ number_format($purchaseNaira) }}</div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--fg-dim);margin-bottom:4px;">Amount Paid</div>
                <div style="font-size:18px;font-weight:600;font-family:'Geist Mono',monospace;color:var(--tan);">₦{{ number_format($ownership->paid_kobo / 100) }}</div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--fg-dim);margin-bottom:4px;">Outstanding</div>
                <div style="font-size:18px;font-weight:600;font-family:'Geist Mono',monospace;color:var(--fg);">₦{{ number_format($ownership->outstanding_kobo / 100) }}</div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--fg-dim);margin-bottom:4px;">Completion</div>
                <div style="font-size:18px;font-weight:600;font-family:'Geist Mono',monospace;color:var(--fg);">{{ number_format($ownership->percent_paid, 1) }}%</div>
            </div>
        </div>
        <div style="background:var(--bg-hover);border-radius:99px;height:10px;overflow:hidden;">
            <div style="height:10px;border-radius:99px;width:{{ min(100, $ownership->percent_paid) }}%;background:linear-gradient(90deg,var(--tan-dark),var(--tan));transition:width 1s var(--ease);"></div>
        </div>
    </div>

    {{-- Value History Table --}}
    @if($histories->count())
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:24px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;">
            <div class="eyebrow">Valuation History</div>
            <span style="font-size:12px;color:var(--fg-dim);">{{ $histories->count() }} {{ $histories->count() === 1 ? 'entry' : 'entries' }}</span>
        </div>
        @php
            $sortedDesc = $histories->sortByDesc('recorded_at')->values();
            $ascForDelta = $histories->sortBy('recorded_at')->values();
        @endphp
        @foreach($sortedDesc as $idx => $entry)
        @php
            // Find previous entry in ascending order
            $ascIdx = $ascForDelta->search(fn($e) => $e->id === $entry->id);
            $prev   = $ascIdx > 0 ? $ascForDelta[$ascIdx - 1] : null;
            $delta  = $prev ? ($entry->value_kobo - $prev->value_kobo) : null;
            $deltaPct = ($prev && $prev->value_kobo > 0) ? round(($delta / $prev->value_kobo) * 100, 2) : null;
            $dc = $delta !== null ? ($delta >= 0 ? 'var(--ok)' : 'var(--danger)') : 'var(--fg-dim)';
        @endphp
        <div style="padding:14px 20px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:1fr auto auto;gap:16px;align-items:center;">
            <div>
                <div style="font-size:14px;font-weight:600;color:var(--fg);font-family:'Geist Mono',monospace;">₦{{ number_format($entry->value_kobo / 100) }}</div>
                @if($entry->notes)<div style="font-size:12px;color:var(--fg-dim);margin-top:2px;">{{ $entry->notes }}</div>@endif
            </div>
            <div style="text-align:right;">
                @if($delta !== null)
                <div style="font-size:13px;font-weight:600;color:{{ $dc }};">
                    {{ $delta >= 0 ? '+' : '' }}₦{{ number_format(abs($delta) / 100) }}
                </div>
                <div style="font-size:11px;color:{{ $dc }};">{{ $delta >= 0 ? '↑' : '↓' }} {{ abs($deltaPct) }}%</div>
                @else
                <div style="font-size:12px;color:var(--fg-faint);">Purchase</div>
                @endif
            </div>
            <div style="text-align:right;min-width:90px;">
                <div style="font-size:13px;color:var(--fg-dim);">{{ $entry->recorded_at->format('d M Y') }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Market Insights for this property --}}
    @if($propertyInsights->isNotEmpty())
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:24px;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;">
            <div class="eyebrow">Market Insights</div>
            <span style="font-size:12px;color:var(--fg-dim);">{{ $propertyInsights->count() }} {{ $propertyInsights->count() === 1 ? 'update' : 'updates' }}</span>
        </div>
        @foreach($propertyInsights as $insight)
        @php
            $iColor = match($insight->change_type) {
                'increase' => 'var(--ok)',
                'decrease' => 'var(--danger)',
                'stable'   => 'var(--fg-dim)',
                default    => 'var(--fg-mute)',
            };
            $iBg = match($insight->change_type) {
                'increase' => 'var(--ok-bg)',
                'decrease' => 'var(--danger-bg)',
                default    => 'var(--bg-raised)',
            };
        @endphp
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:flex-start;gap:14px;">
            @if($insight->change_type)
            <div style="flex-shrink:0;width:36px;height:36px;border-radius:8px;background:{{ $iBg }};display:grid;place-items:center;font-size:16px;font-weight:700;color:{{ $iColor }};">
                {{ $insight->change_arrow }}
            </div>
            @endif
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                    <span style="font-size:14px;font-weight:600;color:var(--fg);">{{ $insight->title }}</span>
                    @if($insight->change_type && $insight->change_percent)
                        <span style="font-size:13px;font-weight:700;color:{{ $iColor }};">
                            {{ $insight->change_arrow }} {{ number_format($insight->change_percent, 2) }}%
                        </span>
                    @endif
                    @if($insight->value)
                        <span style="font-size:13px;font-weight:600;color:var(--tan);">{{ $insight->value }}</span>
                    @endif
                    @if($insight->period)
                        <span style="font-size:11px;color:var(--fg-mute);background:var(--bg-raised);padding:2px 8px;border-radius:99px;border:1px solid var(--line);">{{ $insight->period }}</span>
                    @endif
                </div>
                <p style="font-size:13px;color:var(--fg-dim);margin:0 0 6px;line-height:1.55;">{{ $insight->description }}</p>
                <span style="font-size:11px;color:var(--fg-faint);">{{ $insight->created_at->format('d M Y') }} · {{ $insight->region }}</span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Nav footer --}}
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="{{ route('properties.show', $ownership) }}" class="btn-ghost" style="font-size:13px;">← Balance Tracker</a>
        <a href="{{ route('project-updates.index') }}" class="btn-ghost" style="font-size:13px;">Project Updates</a>
        <a href="{{ route('group.index', $ownership) }}" class="btn-ghost" style="font-size:13px;">Share Access</a>
    </div>
</div>

@if($histories->count() >= 2)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const style = getComputedStyle(document.documentElement);
    const tan   = '#EFC59D';
    const faint = '#4A5876';
    const fg    = '#F0F4FA';
    const grid  = 'rgba(255,255,255,0.06)';

    new Chart(document.getElementById('analyticsChart'), {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Market Value',
                    data: @json($chartValues),
                    borderColor: tan,
                    backgroundColor: 'rgba(239,197,157,0.08)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: tan,
                    pointBorderColor: '#0F1B33',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                },
                {
                    label: 'Purchase Price',
                    data: @json($purchaseLine),
                    borderColor: faint,
                    borderDash: [6, 4],
                    borderWidth: 1.5,
                    tension: 0,
                    fill: false,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F1B33',
                    borderColor: 'rgba(255,255,255,0.12)',
                    borderWidth: 1,
                    titleColor: '#B5C4DD',
                    bodyColor: '#F0F4FA',
                    padding: 12,
                    callbacks: {
                        label: ctx => ' ₦' + new Intl.NumberFormat('en-NG').format(ctx.parsed.y)
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: grid },
                    ticks: { color: '#7A8AA5', font: { family: "'Geist Mono', monospace", size: 11 } }
                },
                y: {
                    grid: { color: grid },
                    ticks: {
                        color: '#7A8AA5',
                        font: { family: "'Geist Mono', monospace", size: 11 },
                        callback: v => '₦' + new Intl.NumberFormat('en-NG').format(v)
                    }
                }
            }
        }
    });
});
</script>
@endif
</x-portal-layout>
