<x-portal-layout>
    <x-slot:title>Browse Properties</x-slot>

    <div class="page-header mb-8">
        <div>
            <div class="eyebrow" style="margin-bottom:6px;">Explore</div>
            <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">Browse properties</h1>
            <p style="font-size:14px;color:var(--fg-mute);margin-top:4px;">View unit photos, check availability, and register your interest.</p>
        </div>
    </div>

    @if(session('success'))
    <div style="background:var(--ok-bg,#f0fdf4);border:1px solid var(--ok-border,#86efac);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--ok-text,#166534);">
        {{ session('success') }}
    </div>
    @endif

    @php
        $allProperties = $available->merge($comingSoon);
    @endphp

    @if($allProperties->isEmpty())
    <div class="card" style="padding:60px;text-align:center;">
        <p style="font-size:14px;color:var(--fg-dim);">No properties listed yet. Check back soon.</p>
    </div>
    @else

    {{-- Available --}}
    @if($available->isNotEmpty())
    <div style="margin-bottom:32px;">
        <div class="eyebrow" style="margin-bottom:16px;">Available now</div>
        <div style="display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));">
            @foreach($available as $property)
            @include('browse._property-card', ['property' => $property, 'myInterests' => $myInterests])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Fully sold / no available units --}}
    @if($comingSoon->isNotEmpty())
    <div>
        <div class="eyebrow" style="margin-bottom:16px;">Other developments</div>
        <div style="display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));">
            @foreach($comingSoon as $property)
            @include('browse._property-card', ['property' => $property, 'myInterests' => $myInterests])
            @endforeach
        </div>
    </div>
    @endif

    @endif

</x-portal-layout>
