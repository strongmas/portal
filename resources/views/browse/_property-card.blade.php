<a href="{{ route('browse.show', $property) }}" class="card"
   style="display:block;text-decoration:none;transition:border-color var(--dur-fast);overflow:hidden;padding:0;"
   onmouseover="this.style.borderColor='var(--line-tan)'" onmouseout="this.style.borderColor='var(--line)'">

    {{-- Cover image --}}
    @if($property->cover_url)
    <div style="height:180px;overflow:hidden;">
        <img src="{{ $property->cover_url }}" alt="{{ $property->development_name }}"
             style="width:100%;height:100%;object-fit:cover;display:block;transition:transform 0.3s ease;"
             onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
    </div>
    @else
    <div style="height:180px;background:var(--bg-raised);display:flex;align-items:center;justify-content:center;">
        <span style="font-size:48px;opacity:0.15;">🏢</span>
    </div>
    @endif

    <div style="padding:20px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;">
            <div>
                <div style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);margin-bottom:3px;">{{ $property->code }}</div>
                <h2 style="font-size:17px;font-weight:600;color:var(--fg);">{{ $property->development_name }}</h2>
                <p style="font-size:12px;color:var(--fg-dim);margin-top:2px;">{{ $property->address }}</p>
            </div>
            @if($property->total_available > 0)
            <span class="chip chip-ok" style="font-size:11px;white-space:nowrap;flex-shrink:0;">{{ $property->total_available }} available</span>
            @else
            <span class="chip chip-dark" style="font-size:11px;white-space:nowrap;flex-shrink:0;">Fully sold</span>
            @endif
        </div>

        {{-- Unit chips --}}
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px;margin-bottom:14px;">
            @foreach($property->units as $unit)
            <div style="background:var(--bg-raised);border:1px solid var(--line);border-radius:7px;padding:6px 10px;">
                <div style="font-size:12px;font-weight:600;color:var(--fg);">{{ $unit->label }}</div>
                <div style="font-size:11px;color:{{ $unit->available > 0 ? 'var(--ok,#16a34a)' : 'var(--fg-dim)' }};margin-top:1px;">
                    {{ $unit->available > 0 ? $unit->available . ' available' : 'Sold out' }}
                </div>
                <div style="font-size:11px;font-family:'Geist Mono',monospace;color:var(--tan);margin-top:1px;">{{ naira($unit->list_price_kobo) }}</div>
            </div>
            @endforeach
        </div>

        <div style="font-size:13px;color:var(--tan);">View photos &amp; details →</div>
    </div>
</a>
