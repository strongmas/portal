<x-portal-layout>
    <x-slot:title>{{ $ownership->display_name }}</x-slot>

    @php $isViewer = $userRole === 'viewer'; @endphp

    {{-- Header --}}
    <div class="page-header mb-8">
        <div>
            <div class="eyebrow" style="margin-bottom:6px;">
                {{ $isViewer ? 'Property Details' : 'Balance Tracker' }} · {{ $ownership->display_name }}
            </div>
            <h1 style="font-size:32px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">
                @if($isViewer)
                    {{ $ownership->property->development_name ?? $ownership->display_name }}
                @else
                    {{ number_format($ownership->percent_paid, 0) }}% of your home is paid off
                @endif
            </h1>
            @if(!$isViewer)
            <p style="font-size:14px;color:var(--fg-mute);margin-top:6px;">
                {{ $ownership->instalments_paid_count }} instalments logged
                · Last: {{ $ownership->payments->last()?->paid_on->format('j M Y') ?? '—' }}
            </p>
            @endif
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            @if($allOwnerships->count() > 1)
                <a href="{{ route('properties.index') }}" class="btn-ghost" style="font-size:13px;">All properties ▾</a>
            @endif
            <a href="{{ route('property-analytics.show', $ownership) }}" class="btn-ghost" style="font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Analytics
            </a>
            @if(!$isViewer)
            <a href="{{ route('group.index', $ownership) }}" class="btn-ghost" style="font-size:13px;display:inline-flex;align-items:center;gap:6px;">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                Share Access
            </a>
            @if($ownership->status === 'completed')
                <span class="chip chip-ok" style="padding:10px 18px;font-size:13px;font-weight:600;">✓ Fully paid</span>
            @else
                <a href="{{ route('payments.create', ['ownership' => $ownership->id]) }}" class="btn-primary" style="font-size:13px;">+ Submit payment</a>
            @endif
            @endif
        </div>
    </div>

    {{-- Hero --}}
    @if(!$isViewer)
    <div class="flex flex-wrap items-end justify-center gap-8 py-8">
        <x-building-viz :percent="$ownership->percent_paid" variant="house" size="lg" :showLabel="true"/>
        <div class="flex flex-col gap-4">
            <x-stat-tile label="Total price" :value="naira($ownership->agreed_price_kobo)"/>
            <x-stat-tile label="Paid" :value="naira($ownership->paid_kobo)" variant="hi">
                {{ number_format($ownership->percent_paid, 0) }}%
            </x-stat-tile>
            <x-stat-tile label="Outstanding" :value="naira($ownership->outstanding_kobo)"/>
        </div>
    </div>
    @endif

    {{-- ── Unit Photos ──────────────────────────────────────────────────── --}}
    @php
        $unitImages     = $ownership->unit?->images ?? collect();
        $propertyImages = $ownership->property->images ?? collect();
        $roomTypes      = ['exterior' => 'Exterior', 'bedroom' => 'Bedroom', 'kitchen' => 'Kitchen', 'bathroom' => 'Bathroom', 'living_area' => 'Living Area'];
        $availableRooms = $unitImages->pluck('room_type')->unique();
        $firstRoom      = $availableRooms->first() ?? 'exterior';
        $hasPhotos      = $unitImages->isNotEmpty() || $propertyImages->isNotEmpty();
    @endphp

    @if($hasPhotos)
    <div style="margin-top:40px;" x-data="{ activeRoom: '{{ $firstRoom }}', lightboxSrc: '', showLightbox: false }">
        <div class="eyebrow" style="margin-bottom:16px;">
            {{ $ownership->unit?->label ?? $ownership->property->development_name }} — Photos
        </div>

        <div class="card" style="padding:0;overflow:hidden;">

            {{-- Room tabs — only show if unit has categorised photos --}}
            @if($unitImages->isNotEmpty())
            <div style="display:flex;gap:0;background:var(--bg-raised);border-bottom:1px solid var(--line);overflow-x:auto;">
                @foreach($roomTypes as $key => $label)
                @if($availableRooms->contains($key))
                <button type="button"
                        @click="activeRoom = '{{ $key }}'"
                        :style="activeRoom === '{{ $key }}' ? 'border-bottom:2px solid var(--tan);color:var(--tan);font-weight:600;' : 'border-bottom:2px solid transparent;color:var(--fg-dim);'"
                        style="padding:11px 18px;font-size:13px;background:none;border:none;border-top:none;border-left:none;border-right:none;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                    {{ $label }}
                    <span style="font-size:11px;margin-left:4px;opacity:0.6;">({{ $unitImages->where('room_type', $key)->count() }})</span>
                </button>
                @endif
                @endforeach

                @if($propertyImages->isNotEmpty())
                <button type="button"
                        @click="activeRoom = '_property'"
                        :style="activeRoom === '_property' ? 'border-bottom:2px solid var(--tan);color:var(--tan);font-weight:600;' : 'border-bottom:2px solid transparent;color:var(--fg-dim);'"
                        style="padding:11px 18px;font-size:13px;background:none;border:none;border-top:none;border-left:none;border-right:none;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                    Development
                    <span style="font-size:11px;margin-left:4px;opacity:0.6;">({{ $propertyImages->count() }})</span>
                </button>
                @endif
            </div>

            {{-- Room photo panels --}}
            @foreach($roomTypes as $key => $label)
            @php $roomImgs = $unitImages->where('room_type', $key)->sortBy('sort_order'); @endphp
            @if($roomImgs->isNotEmpty())
            <div x-show="activeRoom === '{{ $key }}'" x-cloak style="padding:20px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
                    @foreach($roomImgs as $img)
                    <div style="border-radius:10px;overflow:hidden;border:1px solid var(--line);cursor:pointer;"
                         @click="lightboxSrc = '{{ \Storage::disk('public')->url($img->path) }}'; showLightbox = true">
                        <img src="{{ \Storage::disk('public')->url($img->path) }}" alt="{{ $label }}"
                             style="width:100%;height:160px;object-fit:cover;display:block;transition:transform 0.2s ease;"
                             onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach

            {{-- Property-level photos --}}
            @if($propertyImages->isNotEmpty())
            <div x-show="activeRoom === '_property'" x-cloak style="padding:20px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
                    @foreach($propertyImages->sortBy('sort_order') as $img)
                    <div style="border-radius:10px;overflow:hidden;border:2px solid {{ $img->is_cover ? 'var(--tan)' : 'var(--line)' }};cursor:pointer;"
                         @click="lightboxSrc = '{{ \Storage::disk('public')->url($img->path) }}'; showLightbox = true">
                        <img src="{{ \Storage::disk('public')->url($img->path) }}" alt="Development photo"
                             style="width:100%;height:160px;object-fit:cover;display:block;transition:transform 0.2s ease;"
                             onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @else
            {{-- Only property-level photos, no unit room tabs --}}
            <div style="padding:20px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
                    @foreach($propertyImages->sortBy('sort_order') as $img)
                    <div style="border-radius:10px;overflow:hidden;border:2px solid {{ $img->is_cover ? 'var(--tan)' : 'var(--line)' }};cursor:pointer;"
                         @click="lightboxSrc = '{{ \Storage::disk('public')->url($img->path) }}'; showLightbox = true">
                        <img src="{{ \Storage::disk('public')->url($img->path) }}" alt="Development photo"
                             style="width:100%;height:160px;object-fit:cover;display:block;transition:transform 0.2s ease;"
                             onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- Lightbox --}}
        <div x-show="showLightbox" x-cloak
             style="position:fixed;inset:0;background:rgba(0,0,0,0.88);z-index:9999;display:flex;align-items:center;justify-content:center;"
             @click="showLightbox = false" @keydown.escape.window="showLightbox = false">
            <img :src="lightboxSrc" style="max-width:90vw;max-height:88vh;border-radius:8px;box-shadow:0 0 60px rgba(0,0,0,0.5);" @click.stop>
            <button @click="showLightbox = false"
                    style="position:fixed;top:20px;right:24px;background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:22px;width:42px;height:42px;border-radius:50%;cursor:pointer;">✕</button>
        </div>
    </div>
    @endif

    {{-- ── Browse other available units ────────────────────────────────── --}}
    <div style="margin-top:40px;display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div class="eyebrow">Looking for more?</div>
        <a href="{{ route('browse.index') }}" style="font-size:13px;color:var(--tan);text-decoration:none;"
           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Browse all available units →</a>
    </div>

    {{-- Instalment schedule --}}
    @if(!$isViewer && $ownership->instalments->isNotEmpty())
        <div style="margin-top:40px;">
            <div class="eyebrow" style="margin-bottom:16px;">Instalment schedule</div>
            <div class="table-scroll">
                <div class="card" style="padding:0;overflow:hidden;min-width:520px;">
                    <div class="no-collapse" style="display:grid;grid-template-columns:60px 1fr 180px 130px 130px;font-family:'Geist Mono',monospace;font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:var(--fg-dim);background:var(--bg-raised);padding:12px 20px;border-bottom:1px solid var(--line);">
                        <span>#</span><span>Due date</span><span style="text-align:right;">Amount</span><span style="text-align:center;">Status</span><span style="text-align:right;">Action</span>
                    </div>

                    @foreach($ownership->instalments->take(12) as $instalment)
                        <div class="no-collapse" style="display:grid;grid-template-columns:60px 1fr 180px 130px 130px;padding:14px 20px;align-items:center;border-top:1px solid var(--line);font-size:14px;{{ $instalment->status === 'due' ? 'background:var(--info-bg);' : '' }}">
                            <span style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);">#{{ $instalment->instalment_number }}</span>
                            <span style="color:var(--fg-mute);">{{ $instalment->due_date->format('j M Y') }}</span>
                            <span style="font-weight:600;color:var(--fg);text-align:right;font-family:'Geist Mono',monospace;">
                                {{ naira($instalment->status === 'paid' && $instalment->paidPayment ? $instalment->paidPayment->amount_kobo : $instalment->amount_kobo) }}
                            </span>
                            <div style="display:flex;justify-content:center;">
                                @if($instalment->status === 'paid')
                                    <span class="chip chip-ok">paid</span>
                                @elseif($instalment->status === 'due')
                                    <span class="chip chip-tan">due</span>
                                @elseif($instalment->status === 'overdue')
                                    <span class="chip chip-danger">overdue</span>
                                @else
                                    <span class="chip">upcoming</span>
                                @endif
                            </div>
                            <div style="text-align:right;">
                                @if($instalment->status === 'paid' && $instalment->paid_payment_id)
                                    <a href="{{ route('payments.history') }}"
                                       style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;">view →</a>
                                @elseif(in_array($instalment->status, ['due', 'overdue']))
                                    <a href="{{ route('payments.create', ['ownership' => $ownership->id]) }}"
                                       class="btn-primary" style="font-size:12px;padding:6px 14px;border-radius:var(--r-pill);">submit</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($ownership->instalments->count() > 12)
                <p style="text-align:center;font-size:13px;color:var(--fg-dim);margin-top:12px;">
                    Showing 12 of {{ $ownership->instalments->count() }} instalments
                </p>
            @endif
        </div>

    @endif

    {{-- Milestones: shown for viewers, or for owners when no instalment schedule --}}
    @if($isViewer || $ownership->instalments->isEmpty())
        <div style="margin-top:40px;">
            <div class="eyebrow" style="margin-bottom:16px;">Milestones</div>
            @php
                $milestones = [['Deposit',0],['25%',25],['50%',50],['75%',75],['Handover',100]];
                $pct = $ownership->percent_paid;
            @endphp
            <div class="flex items-center">
                @foreach($milestones as $i => [$label, $threshold])
                    <div class="flex flex-col items-center flex-1 gap-2">
                        <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;{{ $pct >= $threshold ? 'background:var(--tan);color:var(--bg-raised);border:2px solid var(--tan);' : 'background:var(--bg-raised);color:var(--fg-dim);border:2px solid var(--line-strong);' }}">
                            {{ $pct >= $threshold ? '✓' : $i + 1 }}
                        </div>
                        <span style="font-size:12px;font-weight:500;color:{{ $pct >= $threshold ? 'var(--fg)' : 'var(--fg-dim)' }};">{{ $label }}</span>
                    </div>
                    @if($i < count($milestones) - 1)
                        <div style="flex:1;height:2px;{{ $pct >= $milestones[$i+1][1] ? 'background:var(--tan);opacity:0.5;' : 'background:var(--line);' }}margin-top:-20px;margin-left:-8px;margin-right:-8px;"></div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Viewer: links to what they can access --}}
    @if($isViewer)
    <div style="margin-top:28px;display:flex;gap:12px;flex-wrap:wrap;">
        <a href="{{ route('project-updates.index') }}" class="btn-ghost" style="font-size:13px;">Project Updates →</a>
        <a href="{{ route('documents.index') }}" class="btn-ghost" style="font-size:13px;">Documents →</a>
        <a href="{{ route('property-analytics.show', $ownership) }}" class="btn-ghost" style="font-size:13px;">Value Analytics →</a>
    </div>
    @endif

</x-portal-layout>
