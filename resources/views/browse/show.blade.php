<x-portal-layout>
    <x-slot:title>{{ $property->development_name }}</x-slot>

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
        <a href="{{ route('browse.index') }}" style="color:var(--fg-dim);text-decoration:none;"
           onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Browse</a>
        <span>/</span>
        <span style="color:var(--fg);">{{ $property->development_name }}</span>
    </div>

    @if(session('success'))
    <div style="background:var(--ok-bg,#f0fdf4);border:1px solid var(--ok-border,#86efac);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--ok-text,#166534);">
        {{ session('success') }}
    </div>
    @endif

    {{-- Property header --}}
    <div style="margin-bottom:28px;">
        <div style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--fg-dim);margin-bottom:4px;">{{ $property->code }}</div>
        <h1 style="font-size:30px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">{{ $property->development_name }}</h1>
        <p style="font-size:13px;color:var(--fg-dim);margin-top:3px;">{{ $property->address }}</p>
        @if($property->description)
        <p style="font-size:14px;color:var(--fg-mute);margin-top:10px;max-width:640px;line-height:1.6;">{{ $property->description }}</p>
        @endif
    </div>

    @php $roomTypes = ['exterior' => 'Exterior', 'bedroom' => 'Bedroom', 'kitchen' => 'Kitchen', 'bathroom' => 'Bathroom', 'living_area' => 'Living Area']; @endphp

    {{-- Unit type sections --}}
    @forelse($property->units as $unit)
    @php
        $isAvailable     = $unit->available > 0;
        $existingInterest = $myInterests->get($unit->id);
        $hasAnyImages    = $unit->images->isNotEmpty();
    @endphp

    <div class="card" style="padding:0;overflow:hidden;margin-bottom:24px;"
         x-data="{ activeRoom: '{{ $unit->images->pluck('room_type')->unique()->first() ?? 'exterior' }}' }">

        {{-- Unit header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--line);">
            <div>
                <h2 style="font-size:17px;font-weight:600;color:var(--fg);">{{ $unit->label }}</h2>
                <div style="display:flex;align-items:center;gap:12px;margin-top:4px;">
                    <span style="font-size:13px;font-family:'Geist Mono',monospace;color:var(--tan);font-weight:600;">{{ naira($unit->list_price_kobo) }}</span>
                    @if($isAvailable)
                    <span class="chip chip-ok" style="font-size:11px;padding:2px 8px;">{{ $unit->available }} available</span>
                    @else
                    <span class="chip chip-danger" style="font-size:11px;padding:2px 8px;">Sold out</span>
                    @endif
                </div>
            </div>

            {{-- Register interest button --}}
            @if($isAvailable)
                @if($existingInterest)
                <div style="text-align:right;">
                    <div style="font-size:12px;color:var(--ok,#16a34a);margin-bottom:4px;">✓ Interest registered</div>
                    <form method="POST" action="{{ route('interests.destroy', $existingInterest->id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;background:none;border:none;cursor:pointer;">
                            Remove interest
                        </button>
                    </form>
                </div>
                @else
                <form method="POST" action="{{ route('interests.store', $property) }}">
                    @csrf
                    <input type="hidden" name="property_unit_id" value="{{ $unit->id }}">
                    <button type="submit" class="btn-primary" style="font-size:13px;">
                        Register Interest
                    </button>
                </form>
                @endif
            @endif
        </div>

        @if($hasAnyImages)
        {{-- Room type tabs --}}
        @php $availableRooms = $unit->images->pluck('room_type')->unique(); @endphp
        <div style="display:flex;gap:0;background:var(--bg-raised);border-bottom:1px solid var(--line);overflow-x:auto;">
            @foreach($roomTypes as $key => $label)
            @if($availableRooms->contains($key))
            <button type="button"
                    @click="activeRoom = '{{ $key }}'"
                    :style="activeRoom === '{{ $key }}' ? 'border-bottom:2px solid var(--tan);color:var(--tan);font-weight:600;' : 'border-bottom:2px solid transparent;color:var(--fg-dim);'"
                    style="padding:10px 18px;font-size:13px;background:none;border:none;border-top:none;border-left:none;border-right:none;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                {{ $label }}
            </button>
            @endif
            @endforeach
        </div>

        {{-- Room photo galleries --}}
        @foreach($roomTypes as $key => $label)
        @php $roomImgs = $unit->images->where('room_type', $key)->sortBy('sort_order'); @endphp
        @if($roomImgs->isNotEmpty())
        <div x-show="activeRoom === '{{ $key }}'" x-cloak style="padding:20px;">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
                @foreach($roomImgs as $img)
                <div style="border-radius:10px;overflow:hidden;border:1px solid var(--line);cursor:pointer;"
                     onclick="openLightbox('{{ addslashes($img->url) }}')">
                    <img src="{{ $img->url }}" alt="{{ $label }}"
                         style="width:100%;height:160px;object-fit:cover;display:block;transition:transform 0.2s ease;"
                         onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach
        @else
        <div style="padding:32px;text-align:center;color:var(--fg-dim);font-size:13px;">
            No photos available for this unit type yet.
        </div>
        @endif
    </div>
    @empty
    <div class="card" style="padding:40px;text-align:center;">
        <p style="font-size:14px;color:var(--fg-dim);">No unit types listed for this property yet.</p>
    </div>
    @endforelse

    {{-- Simple lightbox --}}
    <div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.88);z-index:9999;align-items:center;justify-content:center;"
         onclick="document.getElementById('lightbox').style.display='none'">
        <img id="lightbox-img" src="" style="max-width:90vw;max-height:88vh;border-radius:8px;box-shadow:0 0 60px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
        <button onclick="document.getElementById('lightbox').style.display='none'"
                style="position:fixed;top:20px;right:24px;background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:22px;width:42px;height:42px;border-radius:50%;cursor:pointer;">✕</button>
    </div>

    <script>
    function openLightbox(src) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox').style.display = 'flex';
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.getElementById('lightbox').style.display = 'none';
    });
    </script>

</x-portal-layout>
