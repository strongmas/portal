<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Strongmas' }} · Subscriber's Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-paper min-h-screen">

{{-- Top navigation --}}
<nav class="bg-paper border-b border-ink">
    <div class="max-w-screen-xl mx-auto px-6 flex items-center justify-between h-14">
        {{-- Logo --}}
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 bg-hi border border-ink rounded flex items-center justify-center font-bold text-ink text-sm">S</div>
            <span class="font-semibold text-ink text-base">Strongmas</span>
        </div>

        {{-- Nav links --}}
        <div class="hidden md:flex items-center gap-6">
            @php
                $current      = Route::currentRouteName();
                $ownerAccess  = auth()->user()->hasOwnerAccess();
            @endphp
            @foreach([
                ['home',             'Home',     route('home'),             true],
                ['payments.create',  'Payments', route('payments.create'), $ownerAccess],
                ['payments.history', 'History',  route('payments.history'), $ownerAccess],
                ['properties.index', 'Balance',  route('properties.index'), true],
                ['browse',           'Browse',   route('browse.index'),     true],
                ['support',          'Support',  route('support'),          $ownerAccess],
                ['profile',          'Profile',  route('profile'),          true],
            ] as [$name, $label, $href, $show])
                @if($show)
                <a href="{{ $href }}"
                   class="{{ str_starts_with($current ?? '', $name) ? 'nav-link-active' : 'nav-link' }}">
                    {{ $label }}
                </a>
                @endif
            @endforeach
        </div>

        {{-- Right: bell + avatar --}}
        <div class="flex items-center gap-3">
            <span class="chip text-xs">🔔 0</span>
            <div class="w-9 h-9 rounded-full border border-ink bg-paper-2 flex items-center justify-center font-semibold text-sm text-ink select-none">
                {{ auth()->user()->initials }}
            </div>
        </div>
    </div>
</nav>

{{-- Flash messages --}}
@if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="bg-hi border-b border-ink text-ink text-sm px-6 py-3 flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button @click="show = false" class="text-ink-mute hover:text-ink">✕</button>
    </div>
@endif

{{-- Page content --}}
<main class="max-w-screen-xl mx-auto px-6 py-6">
    {{ $slot }}
</main>

</body>
</html>
