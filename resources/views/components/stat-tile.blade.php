@props(['label' => '', 'value' => '', 'variant' => 'default'])
@php
    $extra = match($variant) {
        'hi'   => 'stat-tile-hi',
        'dark' => '',
        default => '',
    };
@endphp
<div class="stat-tile {{ $extra }}">
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    @if($slot->isNotEmpty())
        <div style="margin-top:4px;font-size:13px;color:var(--tan);font-family:'Geist Mono',monospace;">{{ $slot }}</div>
    @endif
</div>
