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
    @stack('head')
    {{-- Tema liquid glass + latar gradasi. Harus paling akhir supaya menimpa app.css dan template.css. --}}
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}">
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
            <span class="user-chip" translate="no" title="{{ auth()->user()->email }}">{{ auth()->user()->name }}</span>
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
            <span class="theme-label" id="themeLabel"><span class="tl-dark">Gelap</span><span class="tl-light">Terang</span></span>
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
            <a style="--i:6" href="{{ route('template.edit') }}" class="{{ request()->routeIs('template.*') ? 'is-current' : '' }}">Template invoice</a>
            <p class="drawer-foot" translate="no" style="--i:7">{{ \App\Models\BusinessProfile::current()->name }}</p>
        </nav>
    @endauth

    <main class="container page">
        {{-- Masih login tapi membuka halaman Masuk/Daftar: kasih tahu, supaya tidak bingung kenapa terasa "langsung masuk". --}}
        @auth
            @if (request()->routeIs('login', 'register'))
                <div class="alert reveal" role="status" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px 16px; max-width:520px; margin:0 auto 16px; background:var(--accent-soft); border:1px solid var(--accent); color:var(--text);">
                    <span>Kamu sedang masuk sebagai <strong translate="no">{{ auth()->user()->name }}</strong>. Masuk atau daftar di bawah akan berpindah akun.</span>
                    <span style="display:inline-flex; gap:8px;">
                        <a class="btn btn-sm btn-primary" href="{{ route('invoices.index') }}">Lanjut ke daftar invoice</a>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-ghost">Keluar</button>
                        </form>
                    </span>
                </div>
            @endif
        @endauth

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

    @auth
        @include('partials.pdf-picker')
    @endauth
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
