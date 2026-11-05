@props(['title' => 'Admin'])
@php
    use App\Models\InterestRegistration;
    use App\Models\SupportMessage;
    use App\Models\SupportTicket;
    use App\Models\User;
    $authUser         = auth()->user();
    $kycPending       = User::activeBuyers()->whereNull('kyc_verified_at')->count();
    $ticketsPending   = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();
    $chatPending      = SupportMessage::whereRaw('id IN (SELECT MAX(id) FROM support_messages GROUP BY user_id)')
                          ->where('sender', 'buyer')
                          ->whereHas('user', fn($q) => $q->whereNull('archived_at'))->count();
    $interestsPending = InterestRegistration::whereNull('forwarded_at')->count();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="Expires" content="0"/>
    <title>{{ $title }} · Strongmas Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('_partials.mobile-styles')
</head>
<body x-data="{navOpen: false}">

<div class="app-shell">

    {{-- ── Sidebar overlay backdrop ────────────────────────────────────── --}}
    <div class="sidebar-backdrop" :class="{'is-open': navOpen}" @click="navOpen = false"></div>

    {{-- ── Admin Sidebar ──────────────────────────────────────────────────── --}}
    <aside class="sidebar" :class="{'is-open': navOpen}">

        {{-- Logo + admin badge --}}
        <div class="sidebar-logo">
            <div class="sidebar-logo-mark">
                <img src="{{ asset('logo.png') }}" alt="Strongmas" style="width:32px;height:32px;object-fit:contain;border-radius:8px;">
            </div>
            <div class="sidebar-logo-text">
                Strongmas
                <span class="sub">Admin Panel</span>
            </div>
            {{-- Close button (mobile only) --}}
            <button @click="navOpen = false" class="mobile-menu-btn" style="margin-left:auto;" aria-label="Close menu">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Nav --}}
        @php $path = request()->getPathInfo(); @endphp
        <nav class="flex flex-col gap-0.5 flex-1">

            <a href="{{ route('admin.dashboard') }}"
               class="nav-item {{ $path === '/admin' ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>

            @if($authUser->adminCan('buyers'))
            <a href="{{ route('admin.buyers.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/buyers') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                Buyers
            </a>
            @endif

            {{-- Super-admin-only items --}}
            @if($authUser->isSuperAdmin())
            <a href="{{ route('admin.properties.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/properties') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Properties
            </a>

            <a href="{{ route('admin.ownerships.create') }}"
               class="nav-item {{ str_starts_with($path,'/admin/ownerships') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                Link Property
            </a>
            @endif

            {{-- Payments (all admins) --}}
            <a href="{{ route('admin.payments.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/payments') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Payments
            </a>

            {{-- Tickets (all admins) --}}
            <a href="{{ route('admin.tickets.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/tickets') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 10c-.83 0-1.5-.67-1.5-1.5v-5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5z"/><path d="M20.5 10H19V8.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/><path d="M9.5 14c.83 0 1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5S8 21.33 8 20.5v-5c0-.83.67-1.5 1.5-1.5z"/><path d="M3.5 14H5v1.5c0 .83-.67 1.5-1.5 1.5S2 16.33 2 15.5 2.67 14 3.5 14z"/><path d="M14 14.5c0-.83.67-1.5 1.5-1.5h5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-5c-.83 0-1.5-.67-1.5-1.5z"/><path d="M15.5 19H14v1.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5-.67-1.5-1.5-1.5z"/><path d="M10 9.5C10 8.67 9.33 8 8.5 8h-5C2.67 8 2 8.67 2 9.5S2.67 11 3.5 11h5c.83 0 1.5-.67 1.5-1.5z"/><path d="M8.5 5H10V3.5C10 2.67 9.33 2 8.5 2S7 2.67 7 3.5 7.67 5 8.5 5z"/></svg>
                Tickets
                @if($ticketsPending)
                    <span class="nav-badge">{{ $ticketsPending }}</span>
                @endif
            </a>

            {{-- Chat (all admins) --}}
            <a href="{{ route('admin.support.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/support') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                Chat
                @if($chatPending)
                    <span class="nav-badge">{{ $chatPending }}</span>
                @endif
            </a>

            {{-- KYC (all admins) --}}
            <a href="{{ route('admin.kyc.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/kyc') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                KYC
                @if($kycPending)
                    <span class="nav-badge">{{ $kycPending }}</span>
                @endif
            </a>

            {{-- Appointments (all admins) --}}
            <a href="{{ route('admin.appointments.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/appointments') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Appointments
            </a>

            {{-- Interests (all admins) --}}
            <a href="{{ route('admin.interests.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/interests') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                Interests
                @if($interestsPending)
                    <span class="nav-badge">{{ $interestsPending }}</span>
                @endif
            </a>

            {{-- Project Updates (all admins) --}}
            <a href="{{ route('admin.project-updates.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/project-updates') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Project Updates
            </a>

            {{-- Super-admin-only: Property Values, Market Insights, Sales Reps --}}
            @if($authUser->isSuperAdmin())
            <a href="{{ route('admin.property-values.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/property-values') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                Property Values
            </a>

            <a href="{{ route('admin.market-insights.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/market-insights') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Market Insights
            </a>

            <a href="{{ route('admin.sales-reps.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/sales-reps') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Sales Reps
            </a>

            {{-- Staff & Roles --}}
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--fg-faint);padding:12px 12px 4px;">Team</div>
            <a href="{{ route('admin.staff.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/staff') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                Staff
            </a>
            <a href="{{ route('admin.roles.index') }}"
               class="nav-item {{ str_starts_with($path,'/admin/roles') ? 'active' : '' }}"
               @click="navOpen = false">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Roles
            </a>
            @endif

        </nav>

        {{-- Bottom --}}
        <div style="border-top:1px solid var(--line);padding-top:16px;margin-top:8px;">
            <a href="/" target="_blank" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Portal
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
                {{-- Hamburger (mobile only) --}}
                <button class="mobile-menu-btn" @click="navOpen = !navOpen" aria-label="Open menu">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <p class="eyebrow">{{ $title }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span style="font-size:13px;color:var(--fg-dim);">{{ auth()->user()->name }}</span>
                <div class="avatar">{{ auth()->user()->initials ?? 'A' }}</div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="flash-success flex items-center justify-between px-10 py-3 text-sm"
                 x-data="{show:true}" x-show="show" x-cloak>
                <span>✓ {{ session('success') }}</span>
                <button @click="show=false" style="opacity:0.6;" class="ml-auto hover:opacity-100 text-xs">dismiss</button>
            </div>
        @endif
        @if(session('error'))
            <div class="flash-error px-10 py-3 text-sm">{{ session('error') }}</div>
        @endif

        {{-- Page content --}}
        <main style="padding:32px 40px 56px;max-width:1440px;width:100%;">
            {{ $slot }}
        </main>

    </div>
</div>

</body>
</html>
