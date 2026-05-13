<x-portal-layout title="Investment Analytics">

    <div class="page-header mb-8">
        <div>
            <div class="eyebrow eyebrow-tan" style="margin-bottom:6px;">Investment Analytics</div>
            <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Your Analytics</h1>
            <p style="font-size:14px;color:var(--fg-mute);margin-top:4px;">
                Tracking {{ $ownerships->count() }} {{ Str::plural('property', $ownerships->count()) }}
            </p>
        </div>
    </div>

    @forelse($ownerships as $ownership)
    @php
        $a              = $ownership->analytics;
        $isAppreciated  = $a['gainKobo'] >= 0;
        $gainColor      = $isAppreciated ? 'var(--ok)' : 'var(--danger)';
        $gainBg         = $isAppreciated ? 'var(--ok-bg)' : 'var(--danger-bg)';
        $gainArrow      = $isAppreciated ? '↑' : '↓';
        $purchaseNaira  = $ownership->agreed_price_kobo / 100;
        $currentNaira   = $a['currentKobo'] / 100;
        $gainNaira      = $a['gainKobo'] / 100;
    @endphp

    <div class="card" style="margin-bottom:20px;">
        {{-- Card header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
            <div>
                <h2 style="font-size:20px;font-weight:700;color:var(--fg);letter-spacing:-0.01em;margin:0 0 4px;">
                    {{ $ownership->display_name }}
                </h2>
                <p style="font-size:13px;color:var(--fg-mute);margin:0;">{{ $ownership->property->address }}</p>
            </div>
            <a href="{{ route('property-analytics.show', $ownership) }}"
               class="btn-ghost" style="font-size:13px;white-space:nowrap;">
                Full analytics →
            </a>
        </div>

        {{-- KPI row --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px;">

            {{-- Purchase price --}}
            <div style="background:var(--bg-raised);border-radius:12px;padding:16px 18px;">
                <p style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--fg-mute);margin:0 0 8px;">Purchase Price</p>
                <p style="font-size:20px;font-weight:700;color:var(--fg);font-family:'Geist Mono',monospace;margin:0;">
                    ₦{{ number_format($purchaseNaira, 0) }}
                </p>
            </div>

            {{-- Current value --}}
            <div style="background:var(--bg-raised);border-radius:12px;padding:16px 18px;">
                <p style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--fg-mute);margin:0 0 8px;">
                    Current Value
                    @if(!$a['hasHistory'])<span style="font-size:10px;color:var(--fg-mute);font-weight:400;text-transform:none;letter-spacing:0;">(no updates yet)</span>@endif
                </p>
                <p style="font-size:20px;font-weight:700;color:var(--fg);font-family:'Geist Mono',monospace;margin:0;">
                    ₦{{ number_format($currentNaira, 0) }}
                </p>
            </div>

            {{-- Gain / loss --}}
            <div style="background:{{ $gainBg }};border-radius:12px;padding:16px 18px;">
                <p style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--fg-mute);margin:0 0 8px;">
                    {{ $isAppreciated ? 'Gain' : 'Loss' }}
                </p>
                <p style="font-size:20px;font-weight:700;color:{{ $gainColor }};font-family:'Geist Mono',monospace;margin:0;">
                    {{ $gainArrow }} ₦{{ number_format(abs($gainNaira), 0) }}
                </p>
                @if($a['hasHistory'])
                <p style="font-size:12px;color:{{ $gainColor }};margin:4px 0 0;opacity:.8;">
                    {{ $gainArrow }} {{ abs($a['gainPercent']) }}%
                </p>
                @endif
            </div>

            {{-- Payment progress --}}
            <div style="background:var(--bg-raised);border-radius:12px;padding:16px 18px;">
                <p style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--fg-mute);margin:0 0 8px;">Payment Progress</p>
                <p style="font-size:20px;font-weight:700;color:var(--tan);font-family:'Geist Mono',monospace;margin:0 0 8px;">
                    {{ number_format($ownership->percent_paid, 0) }}%
                </p>
                <x-progress-bar :percent="$ownership->percent_paid"/>
            </div>
        </div>

        {{-- Footer link --}}
        <div style="text-align:right;">
            <a href="{{ route('property-analytics.show', $ownership) }}"
               style="font-size:13px;color:var(--hi);text-decoration:none;font-weight:600;">
                View full chart &amp; valuation history →
            </a>
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:80px 20px;color:var(--fg-dim);">
        <p style="font-size:15px;margin:0;">No properties linked to your account yet.</p>
    </div>
    @endforelse

</x-portal-layout>
