<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventory') - Inventory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="{{ route('home') }}">Inventory<span>.</span></a>
            @auth
                <div class="header-account">
                    <span class="user-label">{{ auth()->user()->name }} · {{ auth()->user()->role }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="logout-form">
                        @csrf
                        <button class="nav-link nav-button" type="submit">Keluar</button>
                    </form>
                </div>
            @else
                <nav aria-label="Navigasi publik">
                    <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}"
                       href="{{ route('login') }}">Masuk</a>
                    <a class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}"
                       href="{{ route('register') }}">Daftar</a>
                </nav>
            @endauth
        </div>
    </header>

    @auth
        <div class="app-shell">
            <aside class="app-sidebar">
                <p class="sidebar-heading">MENU UTAMA</p>
                <nav class="sidebar-nav" aria-label="Navigasi utama">
                    @if (auth()->user()->canManageProducts())
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">Dashboard</a>
                    @endif
                    <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
                       href="{{ route('products.index') }}">Produk</a>
                    @if (auth()->user()->canManageProducts())
                        <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                           href="{{ route('categories.index') }}">Kategori</a>
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                           href="{{ route('reports.inventory') }}">Laporan</a>
                        <a class="nav-link {{ request()->routeIs('labels.*') ? 'active' : '' }}"
                           href="{{ route('labels.index') }}">Label barcode</a>
                    @endif
                    <a class="nav-link {{ request()->routeIs('stock-transactions.*') ? 'active' : '' }}"
                       href="{{ route('stock-transactions.index') }}">Pergerakan stok</a>
                    <a class="nav-link {{ request()->routeIs('scanner.*') ? 'active' : '' }}"
                       href="{{ route('scanner.index') }}">Scan produk</a>
                    <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                       href="{{ route('profile.edit') }}">Profil</a>
                    @if (auth()->user()->isAdmin())
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                           href="{{ route('users.index') }}">Pengguna</a>
                    @endif
                </nav>
            </aside>
            <main class="container page-content">
                @include('layouts.notifications')
                @yield('content')
            </main>
        </div>
    @else
        <main class="container page-content">
            @include('layouts.notifications')
            @yield('content')
        </main>
    @endauth
</body>
</html>
