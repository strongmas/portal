@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'input-base']) }}
       style="width:100%;background:var(--bg);color:var(--fg);border-color:var(--line);color-scheme:dark;">
