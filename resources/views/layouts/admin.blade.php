@props(['title' => 'Admin'])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>{{ $title ?? 'Admin' }} · Strongmas Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">

<nav class="bg-white border-b border-gray-200">
    <div class="max-w-screen-xl mx-auto px-6 flex items-center justify-between h-14">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 bg-hi border border-ink rounded flex items-center justify-center font-bold text-ink text-sm">S</div>
            <span class="font-semibold text-ink">Admin Panel</span>
        </div>
        <div class="flex items-center gap-5 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">Dashboard</a>
            <a href="{{ route('admin.buyers.index') }}" class="text-gray-600 hover:text-gray-900">Buyers</a>
            <a href="{{ route('admin.payments.index') }}" class="text-gray-600 hover:text-gray-900">Payments</a>
            <a href="{{ route('admin.interests.index') }}" class="text-gray-600 hover:text-gray-900">Interests</a>
            <a href="{{ route('admin.support.index') }}" class="text-gray-600 hover:text-gray-900">Support</a>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-800">Log out</button>
        </form>
    </div>
</nav>

@if(session('success'))
    <div class="bg-green-50 border-b border-green-200 text-green-800 text-sm px-6 py-3">
        {{ session('success') }}
    </div>
@endif

<main class="max-w-screen-xl mx-auto px-6 py-8">
    {{ $slot }}
</main>
</body>
</html>
