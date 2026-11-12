@extends('layouts.app')

@section('title', 'Password baru')

@section('content')
    <section class="auth-wrap">
        <div class="card auth-card reveal">
            <h1>Password baru</h1>
            <p class="muted">Buat password baru untuk akunmu.</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
                           required readonly maxlength="120" autocomplete="username">
                </div>

                <div class="field">
                    <label for="password">Password baru</label>
                    <div class="pw-wrap">
                        <input id="password" type="password" name="password"
                               required autofocus minlength="8" maxlength="8" autocomplete="new-password"
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

                <button type="submit" class="btn btn-primary btn-block">Simpan password baru</button>
            </form>

            <p class="auth-alt">Tautan kedaluwarsa? <a class="link" href="{{ route('password.request') }}">Minta tautan baru</a></p>
        </div>
    </section>

    <script>
        // Daftar syarat password menyala hijau satu per satu saat terpenuhi.
        // Ini hanya tampilan; server tetap memeriksa ulang di PasswordResetController.
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
