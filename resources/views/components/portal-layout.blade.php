@props(['title' => 'Strongmas'])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="Expires" content="0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Strongmas Subscriber's Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('_partials.mobile-styles')
</head>
<body>

<div class="app-shell" x-data="{navOpen: false}">

    {{-- ── Sidebar overlay backdrop ────────────────────────────────────── --}}
    <div class="sidebar-backdrop" :class="{'is-open': navOpen}" @click="navOpen = false"></div>

    {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
    <aside class="sidebar" :class="{'is-open': navOpen}">

        {{-- Logo --}}
        <div class="sidebar-logo">
            <div class="sidebar-logo-mark">
                <img src="{{ asset('logo.png') }}" alt="Strongmas" style="width:32px;height:32px;object-fit:contain;border-radius:8px;">
            </div>
            <div class="sidebar-logo-text">
                Strongmas
                <span class="sub">Subscriber's Portal</span>
            </div>
            <button @click="navOpen = false" class="mobile-menu-btn" style="margin-left:auto;" aria-label="Close menu">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Nav --}}
        @php
            $r           = Route::currentRouteName() ?? '';
            $ownerAccess = auth()->user()->hasOwnerAccess();
        @endphp
        <nav class="flex flex-col gap-0.5 flex-1" style="overflow-y:auto;">

            <a href="{{ route('home') }}"
               class="nav-item {{ $r === 'home' ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Home
            </a>

            @if($ownerAccess)
            <a href="{{ route('payments.create') }}"
               class="nav-item {{ str_starts_with($r,'payments.create') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Log Payment
            </a>

            <a href="{{ route('payments.history') }}"
               class="nav-item {{ str_starts_with($r,'payments.history') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                History
            </a>
            @endif

            <a href="{{ route('properties.index') }}"
               class="nav-item {{ str_starts_with($r,'properties') || str_starts_with($r,'property-values') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                Balance
            </a>

            <a href="{{ route('project-updates.index') }}"
               class="nav-item {{ str_starts_with($r,'project-updates') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/><circle cx="18" cy="5" r="3" fill="currentColor" stroke="none"/></svg>
                Project Updates
            </a>

            @if($ownerAccess)
            <a href="{{ route('appointments.index') }}"
               class="nav-item {{ str_starts_with($r,'appointments') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Appointments
            </a>
            @endif

            <a href="{{ route('documents.index') }}"
               class="nav-item {{ str_starts_with($r,'documents') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Documents
            </a>

            <a href="{{ route('analytics.index') }}"
               class="nav-item {{ str_starts_with($r,'analytics') || str_starts_with($r,'property-analytics') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Analytics
            </a>

            <a href="{{ route('map.index') }}"
               class="nav-item {{ str_starts_with($r,'map') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Map
            </a>

            @if($ownerAccess)
            <a href="{{ route('support') }}"
               class="nav-item {{ str_starts_with($r,'support') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                Support
            </a>
            @endif

            <a href="{{ route('profile') }}"
               class="nav-item {{ str_starts_with($r,'profile') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profile
            </a>

        </nav>

        {{-- Bottom: user + logout --}}
        <div style="border-top:1px solid var(--line);padding-top:16px;margin-top:8px;">
            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-3 py-2 rounded-[10px] hover:bg-[var(--bg-raised)] transition-colors" @click="navOpen = false">
                @if(auth()->user()?->avatar_path)
                    <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}"
                         alt="{{ auth()->user()->name }}"
                         class="avatar" style="object-fit:cover;">
                @else
                    <div class="avatar">{{ auth()->user()?->initials ?? 'U' }}</div>
                @endif
                <div style="min-width:0;flex:1;">
                    <div style="font-size:13px;font-weight:600;color:var(--fg);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ auth()->user()?->name }}
                    </div>
                    <div style="font-size:11px;color:var(--fg-dim);">Subscriber</div>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit" class="nav-item w-full text-left" style="color:var(--danger);opacity:0.8;">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Log out
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Main area ──────────────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;min-width:0;">

        {{-- Topbar --}}
        <header class="topbar">
            <div class="flex items-center gap-3">
                <button class="mobile-menu-btn" @click="navOpen = !navOpen" aria-label="Open menu">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <p class="eyebrow">{{ $title }}</p>
            </div>
            <div class="flex items-center gap-3">

                {{-- ── Notification Bell ─────────────────────────────────── --}}
                <div x-data="{ open: false }" @click.outside="open = false" style="position:relative;">
                    <button @click="open = !open"
                            style="width:36px;height:36px;border-radius:10px;background:var(--bg-card);border:1px solid var(--line);display:grid;place-items:center;color:var(--fg-mute);transition:all var(--dur-fast);position:relative;"
                            :style="open ? 'color:var(--fg);' : ''"
                            aria-label="Notifications">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                        @if(($_unreadCount ?? 0) > 0)
                        <span style="position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:10px;font-weight:700;width:16px;height:16px;border-radius:50%;display:grid;place-items:center;line-height:1;">
                            {{ min($_unreadCount, 9) }}{{ $_unreadCount > 9 ? '' : '' }}
                        </span>
                        @endif
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open" x-transition
                         style="position:absolute;right:0;top:calc(100% + 8px);width:320px;background:var(--bg-card);border:1px solid var(--line);border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:100;overflow:hidden;">

                        <div style="padding:14px 16px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;">
                            <p style="font-size:14px;font-weight:700;color:var(--fg);margin:0;">Notifications</p>
                            @if(($_unreadCount ?? 0) > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" style="font-size:12px;color:var(--fg-dim);background:none;border:none;cursor:pointer;">Mark all read</button>
                            </form>
                            @endif
                        </div>

                        @forelse(($_recentNotifications ?? collect()) as $notif)
                        @php $d = $notif->data; @endphp
                        <a href="{{ $d['url'] ?? route('notifications.index') }}"
                           style="display:flex;gap:10px;align-items:flex-start;padding:12px 16px;border-bottom:1px solid var(--line);text-decoration:none;{{ $notif->read_at ? '' : 'background:var(--bg-raised);' }}">
                            <div style="width:8px;height:8px;border-radius:50%;background:{{ $notif->read_at ? 'transparent' : 'var(--hi)' }};margin-top:5px;flex-shrink:0;"></div>
                            <div style="flex:1;min-width:0;">
                                <p style="font-size:13px;font-weight:600;color:var(--fg);margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $d['title'] ?? '' }}</p>
                                <p style="font-size:12px;color:var(--fg-dim);margin:0;line-height:1.4;white-space:normal;">{{ Str::limit($d['message'] ?? '', 80) }}</p>
                                <p style="font-size:11px;color:var(--fg-mute);margin:4px 0 0;">{{ $notif->created_at->diffForHumans() }}</p>
                            </div>
                        </a>
                        @empty
                        <div style="padding:24px 16px;text-align:center;color:var(--fg-dim);font-size:13px;">
                            You're all caught up!
                        </div>
                        @endforelse

                        <a href="{{ route('notifications.index') }}"
                           style="display:block;text-align:center;padding:12px;font-size:13px;font-weight:600;color:var(--hi);text-decoration:none;border-top:1px solid var(--line);">
                            View all notifications
                        </a>
                    </div>
                </div>

                {{-- Avatar --}}
                <a href="{{ route('profile') }}">
                    @if(auth()->user()?->avatar_path)
                        <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}"
                             alt="{{ auth()->user()->name }}"
                             class="avatar" style="object-fit:cover;">
                    @else
                        <div class="avatar">{{ auth()->user()?->initials ?? 'U' }}</div>
                    @endif
                </a>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="flash-success flex items-center justify-between px-10 py-3 text-sm">
                <span>{{ session('success') }}</span>
                <button @click="show = false" style="color:var(--ok);opacity:0.7;" class="ml-4 hover:opacity-100">✕</button>
            </div>
        @endif
        @if(session('error'))
            <div class="flash-error px-10 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any() && !$errors->has('ownership_id') && !$errors->has('amount'))
            <div class="flash-error px-10 py-3 text-sm">{{ $errors->first() }}</div>
        @endif

        {{-- Page content --}}
        <main style="padding:32px 40px 56px;max-width:1280px;width:100%;">
            {{ $slot }}
        </main>

    </div>
</div>

</body>
</html>
