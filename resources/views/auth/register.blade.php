@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
    <section class="auth-wrap">
        <div class="card auth-card reveal">
            <h1>Buat akun</h1>
            <p class="muted">Daftar dulu, lalu langsung bisa membuat invoice.</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="field">
                    <label for="name">Nama</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                           required autofocus maxlength="100" autocomplete="name">
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required maxlength="120" autocomplete="username">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="pw-wrap">
                        <input id="password" type="password" name="password"
                               required minlength="8" maxlength="8" autocomplete="new-password"
                               pattern="(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d\s]).{8}"
                               title="Tepat 8 karakter, berisi huruf, angka, dan simbol.">
                        <button type="button" class="pw-toggle" data-pw-toggle aria-pressed="false" aria-label="Tampilkan password">Tampilkan</button>
                    </div>
                    <ul class="rules" id="pwRules">
                        <li data-rule="len">Tepat 8 karakter</li>
                        <li data-rule="letter">Ada huruf</li>
                        <li data-rule="num">Ada angka</li>
                        <li data-rule="sym">Ada simbol (contoh: ! @ # ?)</li>
                    </ul>
                </div>

                <div class="field">
                    <label for="password_confirmation">Ulangi password</label>
                    <div class="pw-wrap">
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               required minlength="8" maxlength="8" autocomplete="new-password">
                        <button type="button" class="pw-toggle" data-pw-toggle aria-pressed="false" aria-label="Tampilkan password">Tampilkan</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Buat akun</button>
            </form>

            <p class="auth-alt">Sudah punya akun? <a class="link" href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>

    <script>
        // Daftar syarat password menyala hijau satu per satu saat terpenuhi.
        // Ini hanya tampilan; server tetap memeriksa ulang di AuthController.
        (function () {
            var input = document.getElementById('password');
            var items = document.querySelectorAll('#pwRules li');
            if (!input || !items.length) { return; }

            var tests = {
                len: function (v) { return v.length === 8; },
                letter: function (v) { return /[A-Za-z]/.test(v); },
                num: function (v) { return /\d/.test(v); },
                sym: function (v) { return /[^A-Za-z\d\s]/.test(v); }
            };

            function check() {
                items.forEach(function (li) {
                    li.classList.toggle('is-ok', tests[li.getAttribute('data-rule')](input.value));
                });
            }

            input.addEventListener('input', check);
            check();
        })();
    </script>
@endsection
