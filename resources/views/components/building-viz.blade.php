@props([
    'percent'   => 0,
    'variant'   => 'tower',
    'size'      => 'md',
    'showLabel' => true,
])

@php
    $p = max(0, min(100, (float) $percent));
    $sizes = ['sm' => [60, 80], 'md' => [120, 160], 'lg' => [180, 240]];
    [$w, $h] = $sizes[$size] ?? $sizes['md'];
@endphp

<div style="display:inline-flex;flex-direction:column;align-items:center;gap:8px;">

    @if($variant === 'tower')
        @php $floors = 8; $filledFloors = ($p / 100) * $floors; @endphp
        {{-- Roof --}}
        <div style="width:0;height:0;border-left:{{ $w * 0.55 }}px solid transparent;border-right:{{ $w * 0.55 }}px solid transparent;border-bottom:28px solid #EFC59D;margin-bottom:-1px;"></div>
        {{-- Body --}}
        <div style="width:{{ $w * 0.92 }}px;height:{{ $h }}px;border:1.5px solid rgba(255,255,255,0.16);border-radius:4px 4px 0 0;display:flex;flex-direction:column-reverse;background:#0F1B33;overflow:hidden;">
            @for($i = 0; $i < $floors; $i++)
                @php $fillRatio = max(0, min(1, $filledFloors - $i)); @endphp
                <div style="flex:1;{{ $i > 0 ? 'border-top:1px solid rgba(255,255,255,0.06);' : '' }}background:linear-gradient(to top,rgba(239,197,157,0.85) {{ $fillRatio*100 }}%,transparent {{ $fillRatio*100 }}%);display:flex;align-items:center;justify-content:space-around;padding:0 6px;">
                    @foreach([0,1,2] as $win)
                        <div style="width:10px;height:40%;border:1px solid rgba(255,255,255,0.12);background:{{ $fillRatio > 0.5 ? 'rgba(239,197,157,0.3)' : 'rgba(255,255,255,0.04)' }};border-radius:1px;"></div>
                    @endforeach
                </div>
            @endfor
        </div>
        <div style="width:{{ $w * 1.05 }}px;height:4px;background:#EFC59D;border-radius:2px;opacity:0.8;"></div>

    @elseif($variant === 'house')
        <svg viewBox="0 0 140 160" width="{{ $w }}" height="{{ $h }}" style="overflow:visible;">
            <defs>
                <linearGradient id="tan-fill-{{ $size }}" x1="0" y1="1" x2="0" y2="0">
                    <stop offset="0%" stop-color="#D4A77A"/>
                    <stop offset="100%" stop-color="#EFC59D"/>
                </linearGradient>
                <clipPath id="house-clip-{{ $size }}">
                    <path d="M 20 80 L 70 30 L 120 80 L 120 140 L 20 140 Z"/>
                </clipPath>
            </defs>
            {{-- Fill --}}
            <rect x="20" y="{{ 140 - (110 * $p / 100) }}" width="100" height="{{ 110 * $p / 100 }}"
                  fill="url(#tan-fill-{{ $size }})" clip-path="url(#house-clip-{{ $size }})" opacity="0.85"/>
            {{-- Outline --}}
            <path d="M 20 80 L 70 30 L 120 80 L 120 140 L 20 140 Z"
                  fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" stroke-linejoin="round"/>
            {{-- Door --}}
            <rect x="60" y="105" width="20" height="35" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.16)" stroke-width="1.2"/>
            <circle cx="76" cy="124" r="1.2" fill="rgba(255,255,255,0.4)"/>
            {{-- Windows --}}
            <rect x="32" y="92" width="16" height="14" fill="rgba(239,197,157,0.15)" stroke="rgba(255,255,255,0.16)" stroke-width="1.2"/>
            <rect x="92" y="92" width="16" height="14" fill="rgba(239,197,157,0.15)" stroke="rgba(255,255,255,0.16)" stroke-width="1.2"/>
            {{-- Chimney --}}
            <rect x="95" y="42" width="10" height="22" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.12)" stroke-width="1.2"/>
            {{-- Ground line --}}
            <line x1="0" y1="142" x2="140" y2="142" stroke="rgba(239,197,157,0.3)" stroke-width="1.5"/>
        </svg>

    @else {{-- blocks --}}
        @php $total = 20; $filled = round(($p / 100) * $total); @endphp
        <div style="width:{{ $w }}px;height:{{ $h }}px;display:grid;grid-template-rows:repeat(10,1fr);gap:2px;padding:2px;border:1px solid rgba(255,255,255,0.12);background:#0F1B33;border-radius:4px;">
            @for($row = 0; $row < 10; $row++)
                @php $rowFromBottom = 9 - $row; @endphp
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px;">
                    @foreach([0, 1] as $col)
                        @php $idx = $rowFromBottom * 2 + $col; $isFilled = $idx < $filled; @endphp
                        <div style="background:{{ $isFilled ? 'rgba(239,197,157,0.8)' : 'rgba(255,255,255,0.04)' }};border:1px solid rgba(255,255,255,0.06);border-radius:1px;"></div>
                    @endforeach
                </div>
            @endfor
        </div>
    @endif

    @if($showLabel)
        <span style="font-family:'Geist Mono',monospace;font-size:11px;color:var(--fg-dim);text-transform:uppercase;letter-spacing:0.1em;">
            {{ number_format($p, 0) }}% paid
        </span>
    @endif
</div>
