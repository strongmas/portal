<x-layouts.admin title="{{ $property->development_name }}">

<div class="flex items-center gap-2 mb-6" style="font-size:13px;color:var(--fg-dim);">
    <a href="{{ route('admin.properties.index') }}" style="color:var(--fg-dim);text-decoration:none;" onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg-dim)'">Properties</a>
    <span>/</span>
    <span style="color:var(--fg);">{{ $property->development_name }}</span>
</div>

<div class="flex items-start justify-between mb-6">
    <div>
        <div style="font-family:'Geist Mono',monospace;font-size:12px;color:var(--fg-dim);margin-bottom:4px;">{{ $property->code }}</div>
        <h1 style="font-size:28px;font-weight:700;color:var(--fg);letter-spacing:-0.02em;">{{ $property->development_name }}</h1>
        <p style="font-size:13px;color:var(--fg-mute);margin-top:2px;">{{ $property->address }}</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.ownerships.create', ['property_id' => $property->id]) }}" class="btn-primary">🔗 Link a buyer</a>
        <a href="{{ route('admin.properties.edit', $property) }}" class="btn-ghost">Edit</a>
        @if($property->ownerships->isEmpty())
        <form method="POST" action="{{ route('admin.properties.destroy', $property) }}"
              onsubmit="return confirm('Permanently delete this property and all its data? This cannot be undone.')"
              style="display:inline;">
            @csrf @method('DELETE')
            <button type="submit" class="btn-ghost" style="color:var(--danger);border-color:rgba(244,114,114,0.3);">Delete</button>
        </form>
        @endif
    </div>
</div>

@php
    $totalCollected = $property->ownerships->sum(fn($o) => $o->payments->sum('amount_kobo'));
    $totalUnitsAll  = $property->units->sum('total_units');
    $soldUnitsAll   = $property->units->sum(fn($u) => $u->ownerships->count());
    $availableAll   = $totalUnitsAll - $soldUnitsAll;
@endphp

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px;">
    @foreach([
        ['Unit types',      $property->units->count(), 'var(--fg)'],
        ['Total units',     $totalUnitsAll,             'var(--fg)'],
        ['Available',       $availableAll,              'var(--ok)'],
        ['Sold',            $soldUnitsAll,              'var(--fg)'],
        ['Total collected', naira($totalCollected),     'var(--tan)'],
    ] as [$label, $val, $clr])
        <div class="card" style="padding:16px;">
            <div class="eyebrow" style="margin-bottom:6px;">{{ $label }}</div>
            <div style="font-family:'Geist Mono',monospace;font-size:22px;font-weight:700;color:{{ $clr }};">{{ $val }}</div>
        </div>
    @endforeach
</div>

{{-- Unit types --}}
<div class="card" style="padding:0;overflow:hidden;margin-bottom:20px;" x-data="{ showAddForm: false }">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line);">
        <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Unit types</h2>
        <button type="button" @click="showAddForm = !showAddForm" class="btn-primary" style="font-size:12px;padding:6px 14px;">+ Add unit type</button>
    </div>

    {{-- Add unit form --}}
    <div x-show="showAddForm" x-cloak style="border-bottom:1px solid var(--line);background:var(--bg-raised);padding:20px;">
        <form method="POST" action="{{ route('admin.properties.units.store', $property) }}" style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;align-items:end;">
            @csrf
            @foreach([
                ['bedrooms',      'Bedrooms',      'number', 'e.g. 3', false, 'Leave blank for studio'],
                ['unit_type',     'Unit type',     'select', '',       true,  ''],
                ['total_units',   'Total units',   'number', 'e.g. 20',true,  ''],
                ['list_price_kobo','List price (₦)','text', 'e.g. 32,500,000', true, 'Admin-only'],
                ['notes',         'Notes',         'text',   'Optional', false, ''],
            ] as [$name, $label, $type, $ph, $req, $hint])
                <div>
                    <label class="eyebrow" style="display:block;margin-bottom:5px;">{{ $label }}@if($req) <span style="color:var(--danger);">*</span>@endif</label>
                    @if($type === 'select')
                        <select name="{{ $name }}" {{ $req ? 'required' : '' }} class="input-base" style="font-size:12px;">
                            <option value="">— select —</option>
                            @foreach(['apartment','terrace','maisonette','duplex','penthouse','bungalow','detached','studio'] as $t)
                                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $type }}" name="{{ $name }}" placeholder="{{ $ph }}" {{ $req ? 'required' : '' }}
                               @if($type === 'number') min="0" max="{{ $name === 'bedrooms' ? 20 : 9999 }}" @endif
                               class="input-base" style="font-size:12px;"/>
                        @if($hint)<p style="font-size:11px;color:var(--fg-dim);margin-top:3px;">{{ $hint }}</p>@endif
                    @endif
                </div>
            @endforeach
            <div style="grid-column:span 5;display:flex;align-items:center;gap:10px;padding-top:4px;">
                <button type="submit" class="btn-primary" style="font-size:13px;">Save unit type</button>
                <button type="button" @click="showAddForm = false" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;background:none;border:none;cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>

    @if($property->units->isEmpty())
        <div style="padding:48px;text-align:center;color:var(--fg-dim);font-size:13px;">
            No unit types added yet.
            <button type="button" @click="showAddForm = true" style="color:var(--tan);text-decoration:underline;text-underline-offset:3px;background:none;border:none;cursor:pointer;margin-left:4px;">Add the first →</button>
        </div>
    @else
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid var(--line);">
                    @foreach(['Unit type','List price','Total','Available','Sold','Notes','Actions'] as $h)
                        <th style="padding:10px 20px;text-align:{{ $loop->last ? 'right' : ($loop->index >= 2 && $loop->index <= 4 ? 'center' : 'left') }};font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            @foreach($property->units as $unit)
                @php
                    $soldCount      = $unit->ownerships->count();
                    $availableCount = max(0, $unit->total_units - $soldCount);
                @endphp
                <tbody x-data="{ editing: false }">
                    <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                        onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                        <td style="padding:12px 20px;font-weight:500;color:var(--fg);">{{ $unit->label }}</td>
                        <td style="padding:12px 20px;font-family:'Geist Mono',monospace;font-weight:600;color:var(--fg);">{{ naira($unit->list_price_kobo) }}</td>
                        <td style="padding:12px 20px;text-align:center;font-weight:600;color:var(--fg);">{{ $unit->total_units }}</td>
                        <td style="padding:12px 20px;text-align:center;">
                            <span class="chip {{ $availableCount > 0 ? 'chip-ok' : 'chip-danger' }}" style="font-size:11px;padding:2px 8px;">{{ $availableCount }}</span>
                        </td>
                        <td style="padding:12px 20px;text-align:center;font-weight:500;color:var(--fg-dim);">{{ $soldCount }}</td>
                        <td style="padding:12px 20px;font-size:12px;color:var(--fg-dim);">{{ $unit->notes ?? '—' }}</td>
                        <td style="padding:12px 20px;text-align:right;">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="editing = !editing"
                                        style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;background:none;border:none;cursor:pointer;"
                                        onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">edit</button>
                                <form method="POST" action="{{ route('admin.properties.units.destroy', [$property, $unit]) }}"
                                      onsubmit="return confirm('Remove this unit type?')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:12px;color:var(--danger);text-decoration:underline;text-underline-offset:3px;background:none;border:none;cursor:pointer;">remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak style="border-top:1px solid var(--line);background:var(--bg-raised);">
                        <td colspan="7" style="padding:16px 20px;">
                            <form method="POST" action="{{ route('admin.properties.units.update', [$property, $unit]) }}"
                                  style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;align-items:end;">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="eyebrow" style="display:block;margin-bottom:5px;">Bedrooms</label>
                                    <input type="number" name="bedrooms" min="0" max="20" value="{{ $unit->bedrooms }}" class="input-base" style="font-size:12px;"/>
                                </div>
                                <div>
                                    <label class="eyebrow" style="display:block;margin-bottom:5px;">Unit type</label>
                                    <select name="unit_type" class="input-base" style="font-size:12px;">
                                        @foreach(['apartment','terrace','maisonette','duplex','penthouse','bungalow','detached','studio'] as $t)
                                            <option value="{{ $t }}" {{ $unit->unit_type === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="eyebrow" style="display:block;margin-bottom:5px;">Total units</label>
                                    <input type="number" name="total_units" required min="1" max="9999" value="{{ $unit->total_units }}" class="input-base" style="font-size:12px;"/>
                                </div>
                                <div>
                                    <label class="eyebrow" style="display:block;margin-bottom:5px;">List price (₦)</label>
                                    <input type="text" name="list_price_kobo" value="{{ number_format($unit->list_price_kobo / 100, 0) }}" class="input-base" style="font-size:12px;"/>
                                </div>
                                <div>
                                    <label class="eyebrow" style="display:block;margin-bottom:5px;">Notes</label>
                                    <input type="text" name="notes" value="{{ $unit->notes }}" class="input-base" style="font-size:12px;"/>
                                </div>
                                <div style="grid-column:span 5;display:flex;align-items:center;gap:10px;padding-top:4px;">
                                    <button type="submit" class="btn-primary" style="font-size:13px;">Update</button>
                                    <button type="button" @click="editing = false" style="font-size:13px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;background:none;border:none;cursor:pointer;">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
            @endforeach
        </table>
    @endif
</div>

{{-- ── Unit Type Images ──────────────────────────────────────────────── --}}
@if($property->units->isNotEmpty())
<div class="card" style="padding:0;overflow:hidden;margin-bottom:20px;" x-data="{ activeUnit: {{ $property->units->first()->id }}, activeRoom: 'exterior' }">
    <div style="padding:16px 20px;border-bottom:1px solid var(--line);">
        <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Unit Room Images</h2>
        <p style="font-size:12px;color:var(--fg-dim);margin-top:3px;">Upload exterior, bedroom, kitchen, bathroom and living area photos per unit type. Buyers of each unit type will see these images.</p>
    </div>

    {{-- Unit type tabs --}}
    <div style="display:flex;gap:0;border-bottom:1px solid var(--line);overflow-x:auto;">
        @foreach($property->units as $unit)
        <button type="button"
                @click="activeUnit = {{ $unit->id }}; activeRoom = 'exterior'"
                :style="activeUnit === {{ $unit->id }} ? 'border-bottom:2px solid var(--hi);color:var(--fg);font-weight:600;' : 'border-bottom:2px solid transparent;color:var(--fg-dim);'"
                style="padding:12px 20px;font-size:13px;background:none;border:none;border-top:none;border-left:none;border-right:none;cursor:pointer;white-space:nowrap;flex-shrink:0;">
            {{ $unit->label }}
            <span style="font-size:11px;margin-left:6px;opacity:0.7;">({{ $unit->images->count() }} imgs)</span>
        </button>
        @endforeach
    </div>

    @foreach($property->units as $unit)
    <div x-show="activeUnit === {{ $unit->id }}" x-cloak>
        @php $roomTypes = ['exterior' => 'Exterior', 'bedroom' => 'Bedroom', 'kitchen' => 'Kitchen', 'bathroom' => 'Bathroom', 'living_area' => 'Living Area']; @endphp

        {{-- Room type sub-tabs --}}
        <div style="display:flex;gap:0;padding:0 20px;background:var(--bg-raised);border-bottom:1px solid var(--line);overflow-x:auto;">
            @foreach($roomTypes as $key => $label)
            @php $count = $unit->images->where('room_type', $key)->count(); @endphp
            <button type="button"
                    @click="activeRoom = '{{ $key }}'"
                    :style="activeRoom === '{{ $key }}' ? 'border-bottom:2px solid var(--tan);color:var(--tan);font-weight:600;' : 'border-bottom:2px solid transparent;color:var(--fg-dim);'"
                    style="padding:10px 16px;font-size:12px;background:none;border:none;border-top:none;border-left:none;border-right:none;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                {{ $label }}
                @if($count > 0)<span style="font-size:10px;margin-left:4px;opacity:0.7;">({{ $count }})</span>@endif
            </button>
            @endforeach
        </div>

        @foreach($roomTypes as $key => $label)
        <div x-show="activeRoom === '{{ $key }}'" x-cloak style="padding:20px;">
            @php $roomImages = $unit->images->where('room_type', $key); @endphp

            {{-- Upload form --}}
            <form method="POST"
                  action="{{ route('admin.unit-images.store', [$property, $unit]) }}"
                  enctype="multipart/form-data"
                  style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
                @csrf
                <input type="hidden" name="room_type" value="{{ $key }}">
                <label class="btn-primary" style="cursor:pointer;font-size:12px;padding:6px 14px;">
                    + Upload {{ $label }} Photos
                    <input type="file" name="images[]" multiple accept="image/*" style="display:none;"
                           onchange="this.closest('form').submit()">
                </label>
                <span style="font-size:12px;color:var(--fg-dim);">{{ $roomImages->count() }} photo{{ $roomImages->count() !== 1 ? 's' : '' }}</span>
            </form>

            @if($roomImages->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                @foreach($roomImages->sortBy('sort_order') as $img)
                <div style="position:relative;border-radius:10px;overflow:hidden;border:2px solid var(--line);">
                    <img src="{{ asset('storage/' . $img->path) }}"
                         style="width:140px;height:100px;object-fit:cover;display:block;" alt="{{ $label }}">
                    <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.6);padding:6px;display:flex;justify-content:center;">
                        <form method="POST" action="{{ route('admin.unit-images.destroy', [$property, $unit, $img]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this image?')"
                                    style="background:rgba(239,68,68,0.7);border:none;color:#fff;font-size:10px;padding:3px 8px;border-radius:4px;cursor:pointer;">✕ Remove</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div style="padding:24px;text-align:center;background:var(--bg-raised);border-radius:8px;color:var(--fg-dim);font-size:13px;border:1px dashed var(--line);">
                No {{ strtolower($label) }} photos yet. Click "Upload" to add some.
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endforeach
</div>
@endif

{{-- Buyers linked --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--line);">
        <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Buyers linked to this development</h2>
    </div>

    @if($property->ownerships->isEmpty())
        <div style="padding:48px;text-align:center;color:var(--fg-dim);font-size:13px;">
            No buyers linked yet.
            <a href="{{ route('admin.ownerships.create', ['property_id' => $property->id]) }}" style="color:var(--tan);text-decoration:underline;text-underline-offset:3px;margin-left:4px;">Link a buyer →</a>
        </div>
    @else
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid var(--line);">
                    @foreach(['Buyer','Unit','Plan','Agreed price','Paid','Progress','Status','Actions'] as $h)
                        <th style="padding:10px 20px;text-align:{{ in_array($h,['Agreed price','Paid']) ? 'right' : (in_array($h,['Progress','Status']) ? 'center' : 'left') }};font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($property->ownerships as $ownership)
                    @php
                        $paid = $ownership->payments->sum('amount_kobo');
                        $pct  = $ownership->agreed_price_kobo > 0 ? round(($paid / $ownership->agreed_price_kobo) * 100) : 0;
                    @endphp
                    <tr style="border-top:1px solid var(--line);transition:background var(--dur-fast);"
                        onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background='transparent'">
                        <td style="padding:12px 20px;">
                            <a href="{{ route('admin.buyers.show', $ownership->user) }}"
                               style="font-weight:500;color:var(--fg);text-decoration:none;display:block;"
                               onmouseover="this.style.color='var(--tan)'" onmouseout="this.style.color='var(--fg)'">{{ $ownership->user->name }}</a>
                            <div style="font-size:11px;color:var(--fg-dim);">{{ $ownership->user->email }}</div>
                        </td>
                        <td style="padding:12px 20px;font-size:12px;color:var(--fg-dim);">{{ $ownership->unit?->label ?? '—' }}</td>
                        <td style="padding:12px 20px;font-size:12px;color:var(--fg-dim);">{{ ucfirst($ownership->payment_plan) }}<br>{{ $ownership->total_instalments }} inst.</td>
                        <td style="padding:12px 20px;text-align:right;font-family:'Geist Mono',monospace;font-weight:700;color:var(--fg);">{{ naira($ownership->agreed_price_kobo) }}</td>
                        <td style="padding:12px 20px;text-align:right;font-family:'Geist Mono',monospace;font-weight:600;color:var(--ok);">{{ naira($paid) }}</td>
                        <td style="padding:12px 20px;min-width:120px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;"><x-progress-bar :percent="$pct"/></div>
                                <span style="font-size:11px;color:var(--fg-dim);flex-shrink:0;">{{ $pct }}%</span>
                            </div>
                        </td>
                        <td style="padding:12px 20px;text-align:center;">
                            @php
                                $chipClass = match($ownership->status) {
                                    'active'    => 'chip-ok',
                                    'completed' => 'chip-tan',
                                    default     => 'chip-dark',
                                };
                            @endphp
                            <span class="chip {{ $chipClass }}" style="font-size:10px;padding:2px 7px;">{{ $ownership->status }}</span>
                        </td>
                        <td style="padding:12px 20px;text-align:right;">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.buyers.show', $ownership->user) }}" style="font-size:12px;color:var(--tan);text-decoration:underline;text-underline-offset:3px;">profile</a>
                                <a href="{{ route('admin.ownerships.edit', $ownership) }}" style="font-size:12px;color:var(--fg-dim);text-decoration:underline;text-underline-offset:3px;" onmouseover="this.style.color='var(--fg)'" onmouseout="this.style.color='var(--fg-dim)'">edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ── Property Images ─────────────────────────────────────────────── --}}
<div class="card" style="padding:0;overflow:hidden;margin-top:20px;" x-data="{ reordering: false }">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line);">
        <h2 style="font-size:15px;font-weight:600;color:var(--fg);">Property Images</h2>
        <label style="cursor:pointer;" class="btn-primary" style="font-size:12px;padding:6px 14px;">
            + Upload Images
            <form id="img-upload-form" method="POST" action="{{ route('admin.property-images.store', $property) }}" enctype="multipart/form-data" style="display:none;">
                @csrf
                <input type="file" name="images[]" multiple accept="image/*"
                       onchange="document.getElementById('img-upload-form').submit()">
            </form>
        </label>
    </div>

    @if($property->images->count())
    <div style="padding:20px;display:flex;flex-wrap:wrap;gap:14px;" id="image-grid">
        @foreach($property->images->sortBy('sort_order') as $img)
        <div style="position:relative;border-radius:10px;overflow:hidden;border:2px solid {{ $img->is_cover ? 'var(--hi)' : 'var(--line)' }};" data-id="{{ $img->id }}">
            <img src="{{ asset('storage/' . $img->path) }}" style="width:140px;height:100px;object-fit:cover;display:block;" alt="{{ $img->caption }}">
            @if($img->is_cover)
            <span style="position:absolute;top:6px;left:6px;background:var(--hi);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;">COVER</span>
            @endif
            <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.6);display:flex;gap:4px;padding:6px;justify-content:center;">
                @if(!$img->is_cover)
                <form method="POST" action="{{ route('admin.property-images.cover', [$property, $img]) }}">
                    @csrf
                    <button type="submit" style="background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:10px;padding:3px 8px;border-radius:4px;cursor:pointer;">Set cover</button>
                </form>
                @endif
                <form method="POST" action="{{ route('admin.property-images.destroy', [$property, $img]) }}">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete this image?')"
                            style="background:rgba(239,68,68,0.7);border:none;color:#fff;font-size:10px;padding:3px 8px;border-radius:4px;cursor:pointer;">✕</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div style="padding:40px;text-align:center;color:var(--fg-dim);font-size:13px;">No images uploaded yet. Click "Upload Images" to add some.</div>
    @endif
</div>

{{-- ── Buyer Documents ──────────────────────────────────────────────── --}}
<div class="card" style="padding:0;overflow:hidden;margin-top:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line);">
        <div>
            <h2 style="font-size:15px;font-weight:600;color:var(--fg);margin:0 0 2px;">Buyer Documents</h2>
            <p style="font-size:12px;color:var(--fg-dim);margin:0;">Documents uploaded by buyers. Download to verify, flag if incorrect.</p>
        </div>
        <a href="{{ route('admin.documents.index') }}" style="font-size:12px;color:var(--hi);text-decoration:none;font-weight:600;">View all →</a>
    </div>

    @if($property->documents->count())
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="border-bottom:1px solid var(--line);">
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Document</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Uploaded by</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Status</th>
                <th style="padding:10px 20px;text-align:left;font-family:'Geist Mono',monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:var(--fg-dim);font-weight:400;">Date</th>
                <th style="padding:10px 20px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($property->documents->sortByDesc('created_at') as $doc)
            <tr x-data="{ showFlag: false }" style="border-top:1px solid var(--line);">
                <td style="padding:12px 20px;">
                    <p style="font-weight:600;color:var(--fg);margin:0 0 2px;">{{ $doc->title }}</p>
                    <p style="font-size:11px;color:var(--fg-dim);margin:0;">{{ $doc->type_label }} · v{{ $doc->version }}</p>
                    @if($doc->flag_reason)
                    <p style="font-size:11px;color:#ca8a04;margin:4px 0 0;font-style:italic;">"{{ Str::limit($doc->flag_reason, 70) }}"</p>
                    @endif
                </td>
                <td style="padding:12px 20px;color:var(--fg-dim);">
                    {{ $doc->uploader?->name ?? '—' }}
                </td>
                <td style="padding:12px 20px;">
                    @if($doc->status === 'flagged')
                        <span class="chip" style="background:#fef9c3;color:#713f12;border:1px solid #fde047;font-size:10px;padding:2px 8px;">Flagged</span>
                    @elseif($doc->status === 'verified')
                        <span class="chip chip-ok" style="font-size:10px;padding:2px 8px;">Verified</span>
                    @else
                        <span class="chip chip-dark" style="font-size:10px;padding:2px 8px;">Pending Review</span>
                    @endif
                </td>
                <td style="padding:12px 20px;color:var(--fg-mute);">{{ $doc->created_at->format('d M Y') }}</td>
                <td style="padding:12px 20px;text-align:right;vertical-align:top;">
                    <div style="display:flex;align-items:center;gap:12px;justify-content:flex-end;flex-wrap:wrap;">
                        <a href="{{ route('admin.documents.download', $doc) }}"
                           style="font-size:12px;font-weight:600;color:var(--hi);text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:4px;">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download
                        </a>

                        @if($doc->status !== 'verified')
                        <form method="POST" action="{{ route('admin.documents.verify', $doc) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" style="font-size:12px;color:var(--ok);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;">Verify</button>
                        </form>
                        @endif

                        @if($doc->status === 'flagged')
                        <form method="POST" action="{{ route('admin.documents.unflag', $doc) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" style="font-size:12px;color:var(--fg-dim);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;">Clear Flag</button>
                        </form>
                        @elseif($doc->status !== 'verified')
                        <button type="button" @click="showFlag = !showFlag"
                                style="font-size:12px;color:#ca8a04;background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;">
                            Flag
                        </button>
                        @endif

                        <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}" style="display:inline;" onsubmit="return confirm('Delete this document?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:12px;color:var(--danger);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;">Delete</button>
                        </form>
                    </div>

                    <div x-show="showFlag" x-cloak style="margin-top:10px;">
                        <form method="POST" action="{{ route('admin.documents.flag', $doc) }}" style="display:flex;flex-direction:column;gap:8px;min-width:200px;">
                            @csrf @method('PATCH')
                            <textarea name="flag_reason" rows="2" class="input-base" placeholder="Reason (optional)…" style="font-size:12px;resize:none;"></textarea>
                            <div style="display:flex;gap:8px;justify-content:flex-end;">
                                <button type="button" @click="showFlag = false" style="font-size:12px;color:var(--fg-dim);background:none;border:none;cursor:pointer;">Cancel</button>
                                <button type="submit" class="btn-primary" style="font-size:12px;padding:5px 14px;background:#ca8a04;border-color:#ca8a04;">Flag &amp; Notify</button>
                            </div>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="padding:40px;text-align:center;color:var(--fg-dim);font-size:13px;">No documents uploaded by buyers yet for this property.</div>
    @endif
</div>

</x-layouts.admin>
