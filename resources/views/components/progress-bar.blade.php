@props(['percent' => 0, 'class' => ''])
<div class="progress-bar {{ $class }}">
    <div class="progress-bar-fill" style="width: {{ min(100, max(0, $percent)) }}%"></div>
</div>
