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
        <div style="padding:18px 24px 0;border-bottom:1px solid var(--line);background:var(--bg-raised);">
            <div style="display:flex;gap:6px;overflow-x:auto;padding-bottom:0;">
                @foreach($roomTypes as $key => $label)
                @if($availableRooms->contains($key))
                <button type="button"
                        @click="activeRoom = '{{ $key }}'"
                        :style="activeRoom === '{{ $key }}'
                            ? 'background:var(--tan);color:#0a1428;border-color:var(--tan);font-weight:700;letter-spacing:.04em;'
                            : 'background:transparent;color:var(--fg-dim);border-color:transparent;font-weight:500;letter-spacing:.04em;'"
                        style="padding:8px 20px;font-size:11px;text-transform:uppercase;border:1px solid transparent;
                               border-bottom:none;border-radius:10px 10px 0 0;cursor:pointer;
                               white-space:nowrap;flex-shrink:0;transition:all .18s ease;margin-bottom:-1px;
                               position:relative;z-index:1;"
                        onmouseover="if(this.style.background==='transparent'||this.style.background===''){this.style.color='var(--fg)';this.style.background='var(--bg-hover)'}"
                        onmouseout="if(this.getAttribute('x-bind:style').includes('transparent')){this.style.color='';this.style.background=''}">
                    {{ $label }}
                </button>
                @endif
                @endforeach
            </div>
        </div>

        {{-- Room photo galleries --}}
        @foreach($roomTypes as $key => $label)
        @php $roomImgs = $unit->images->where('room_type', $key)->sortBy('sort_order'); @endphp
        @if($roomImgs->isNotEmpty())
        <div x-show="activeRoom === '{{ $key }}'" x-cloak style="padding:28px 24px;">
            {{-- Section label --}}
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                <span style="font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
                             color:var(--tan);font-family:'Geist Mono',monospace;">{{ $label }}</span>
                <div style="flex:1;height:1px;background:linear-gradient(to right,rgba(239,197,157,.25),transparent);"></div>
                <span style="font-size:11px;color:var(--fg-faint);font-family:'Geist Mono',monospace;">{{ $roomImgs->count() }} {{ Str::plural('photo', $roomImgs->count()) }}</span>
            </div>
            {{-- First image hero + rest in grid --}}
            @php $heroImg = $roomImgs->first(); $restImgs = $roomImgs->slice(1); @endphp
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                {{-- Hero --}}
                <div style="grid-column:span 2;border-radius:14px;overflow:hidden;cursor:pointer;position:relative;
                            border:1px solid rgba(239,197,157,.12);box-shadow:0 4px 24px rgba(0,0,0,.3);"
                     onclick="openLightbox('{{ addslashes($heroImg->url) }}')"
                     onmouseover="this.querySelector('img').style.transform='scale(1.03)'"
                     onmouseout="this.querySelector('img').style.transform='scale(1)'">
                    <img src="{{ $heroImg->url }}" alt="{{ $label }}"
                         style="width:100%;height:340px;object-fit:cover;display:block;transition:transform .5s ease;">
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(5,14,30,.45) 0%,transparent 55%);
                                pointer-events:none;transition:opacity .3s;"
                         class="img-overlay"></div>
                    <div style="position:absolute;bottom:14px;right:14px;display:flex;align-items:center;gap:5px;
                                background:rgba(5,14,30,.65);border:1px solid rgba(255,255,255,.12);
                                border-radius:8px;padding:5px 10px;backdrop-filter:blur(6px);">
                        <svg width="12" height="12" fill="none" stroke="rgba(255,255,255,.7)" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                        <span style="font-size:10px;color:rgba(255,255,255,.7);letter-spacing:.05em;font-weight:600;">EXPAND</span>
                    </div>
                </div>
                {{-- Rest --}}
                @foreach($restImgs as $img)
                <div style="border-radius:12px;overflow:hidden;cursor:pointer;position:relative;
                            border:1px solid rgba(239,197,157,.1);box-shadow:0 2px 12px rgba(0,0,0,.25);"
                     onclick="openLightbox('{{ addslashes($img->url) }}')">
                    <img src="{{ $img->url }}" alt="{{ $label }}"
                         style="width:100%;height:200px;object-fit:cover;display:block;transition:transform .5s ease;"
                         onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                    <div style="position:absolute;inset:0;background:rgba(5,14,30,.15);pointer-events:none;transition:opacity .3s;"
                         onmouseover="this.style.opacity=0" onmouseout="this.style.opacity=1"></div>
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

    {{-- Property Documents (admin-uploaded, visible to all) --}}
    @if(isset($propertyDocuments) && $propertyDocuments->isNotEmpty())
    <div style="margin-top:32px;">
        <h2 style="font-size:18px;font-weight:700;color:var(--fg);letter-spacing:-0.01em;margin-bottom:4px;">Property Documents</h2>
        <p style="font-size:13px;color:var(--fg-dim);margin:0 0 16px;">Official documents shared by Strongmas for this development.</p>
        <div class="card" style="padding:0;overflow:hidden;">
            @foreach($propertyDocuments as $doc)
            <div style="padding:16px 20px;border-bottom:1px solid var(--line);{{ $loop->last ? 'border-bottom:none;' : '' }}display:flex;align-items:center;gap:14px;"
                 onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                <div style="width:36px;height:36px;border-radius:8px;background:var(--tan)18;display:grid;place-items:center;flex-shrink:0;">
                    <svg width="17" height="17" fill="none" stroke="var(--tan)" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:600;color:var(--fg);margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $doc->title }}</p>
                    <p style="font-size:12px;color:var(--fg-dim);margin:0;">{{ $doc->type_label }} · {{ $doc->created_at->format('d M Y') }}</p>
                </div>
                <a href="{{ route('documents.download', $doc) }}"
                   style="display:flex;align-items:center;gap:5px;font-size:13px;font-weight:600;color:var(--hi);text-decoration:none;white-space:nowrap;flex-shrink:0;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

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
