<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sewa Garasi')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="border-b bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="font-semibold">Sewa Garasi</a>
            @auth
                <div class="flex items-center gap-4 text-sm">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.garages.index') }}" class="hover:underline">Garasi</a>
                        <a href="{{ route('admin.tenants.index') }}" class="hover:underline">Verifikasi</a>
                        <a href="{{ route('admin.rentals.index') }}" class="hover:underline">Perpanjang Sewa</a>
                        <a href="{{ route('admin.reports.index') }}" class="hover:underline">Laporan</a>
                    @else
                        <a href="{{ route('rentals.create') }}" class="hover:underline">Sewa Garasi</a>
                    @endif
                    <span class="text-gray-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-600 hover:underline">Logout</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @if (session('success'))
            <div class="mb-4 rounded bg-green-100 px-4 py-3 text-green-800">{{ session('success') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>