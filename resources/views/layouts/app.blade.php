<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Invoicely') - Invoicely</title>
    {{-- Dipasang sebelum CSS dimuat supaya tema tersimpan langsung dipakai (tidak berkedip terang dulu). --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t) { t = 'light'; } // pertama kali dibuka: selalu terang. Pilihan pengguna tetap diingat.
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="topbar">
        {{-- Tombol menu hanya untuk yang sudah login. --}}
        @auth
            <button type="button" class="menu-btn" id="menuBtn" aria-label="Buka menu" aria-expanded="false" aria-controls="drawer">
                <span></span><span></span><span></span>
            </button>
        @endauth

        <a class="brand" href="{{ auth()->check() ? route('invoices.index') : route('home') }}">Invoicely</a>

        @auth
            <span class="user-chip" title="{{ auth()->user()->email }}">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="btn btn-sm btn-ghost">Keluar</button>
            </form>
        @endauth

        {{-- Tamu (halaman masuk/daftar): jalan kembali ke halaman pembuka. --}}
        @guest
            <a class="btn btn-sm btn-ghost" href="{{ route('home') }}">Kembali ke beranda</a>
        @endguest

        <button type="button" class="theme-switch" id="themeSwitch" role="switch" aria-checked="false" aria-label="Ganti mode tampilan">
            <span class="theme-label" id="themeLabel">Terang</span>
            <span class="theme-track"><span class="theme-knob"></span></span>
        </button>
    </header>

    @auth
        <div class="scrim" id="scrim"></div>
        <nav class="drawer" id="drawer" aria-label="Menu utama" inert>
            <p class="drawer-title" style="--i:0">Menu</p>
            <a style="--i:1" href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') && ! request('status') ? 'is-current' : '' }}">Daftar invoice</a>
            <a style="--i:2" href="{{ route('invoices.create') }}" class="{{ request()->routeIs('invoices.create') ? 'is-current' : '' }}">Buat invoice</a>
            <a style="--i:3" href="{{ route('invoices.index', ['status' => 'unpaid']) }}" class="{{ request('status') === 'unpaid' ? 'is-current' : '' }}">Belum lunas</a>
            <a style="--i:4" href="{{ route('invoices.index', ['status' => 'paid']) }}" class="{{ request('status') === 'paid' ? 'is-current' : '' }}">Lunas</a>
            <a style="--i:5" href="{{ route('business.edit') }}" class="{{ request()->routeIs('business.*') ? 'is-current' : '' }}">Data usaha</a>
            <p class="drawer-foot" style="--i:6">{{ \App\Models\BusinessProfile::current()->name }}</p>
        </nav>
    @endauth

    <main class="container page">
        @yield('content')
    </main>

    @if (session('success'))
        <div class="toast" id="toast" role="status">{{ session('success') }}</div>
    @endif

    <div class="modal" id="modal" hidden>
        <div class="modal-scrim" data-close></div>
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <h3 id="modalTitle">Konfirmasi</h3>
            <p id="modalText"></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close>Batal</button>
                <button type="button" class="btn btn-danger" id="modalOk">Ya, lanjutkan</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
