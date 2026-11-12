@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <section class="auth-wrap">
        <div class="card auth-card reveal">
            <h1>Masuk</h1>
            <p class="muted">Masuk untuk membuat dan mengelola invoice.</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="pw-wrap">
                        <input id="password" type="password" name="password"
                               required maxlength="72" autocomplete="current-password">
                        <button type="button" class="pw-toggle" data-pw-toggle aria-pressed="false" aria-label="Tampilkan password">Tampilkan</button>
                    </div>
                    <a class="link forgot-link" href="{{ route('password.request') }}">Lupa password?</a>
                </div>

                <label class="check">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span>Ingat saya di perangkat ini</span>
                </label>

                <button type="submit" class="btn btn-primary btn-block" id="loginBtn"
                        data-lock="{{ session('lock_seconds', 0) }}">Masuk</button>
            </form>

            <p class="auth-alt">Belum punya akun? <a class="link" href="{{ route('register') }}">Daftar</a></p>
        </div>
    </section>

    <script>
        // Kalau login sedang dikunci, tombol Masuk dinonaktifkan dan menghitung mundur.
        // Ini hanya tampilan; yang benar-benar menolak login adalah server.
        (function () {
            var btn = document.getElementById('loginBtn');
            var left = parseInt(btn.getAttribute('data-lock') || '0', 10);
            if (!left) { return; }

            var label = btn.textContent;
            btn.disabled = true;

            function tick() {
                if (left <= 0) {
                    btn.disabled = false;
                    btn.textContent = label;
                    return;
                }
                btn.textContent = 'Coba lagi dalam ' + left + ' detik';
                left--;
                setTimeout(tick, 1000);
            }
            tick();
        })();
    </script>
@endsection
