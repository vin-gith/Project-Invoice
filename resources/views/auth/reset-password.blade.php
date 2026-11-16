@extends('layouts.app')

@section('title', 'Password baru')

@section('content')
    <section class="auth-wrap">
        <div class="card auth-card reveal">
            <h1>Password baru</h1>
            <p class="muted">Masukkan kode 6 angka dari email, lalu buat password baru. Kode berlaku {{ $minutes }} menit.</p>

            @if (session('status'))
                <div class="alert alert-ok" role="status">{{ session('status') }}</div>
            @endif

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

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
                           required maxlength="120" autocomplete="username"
>
                </div>

                <div class="field">
                    <label for="code">Kode dari email</label>
                    <input id="code" type="text" name="code" value="" inputmode="numeric" autocomplete="one-time-code"
                           required maxlength="6" pattern="[0-9]{6}" title="6 angka"
                           {{ old('email', $email) !== '' ? 'autofocus' : '' }}
                           style="font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 1.5rem; letter-spacing: 0.45em; text-align: center; padding-left: 0.45em;">
                    <small class="field-hint">Cek kotak masuk dan folder spam.</small>
                </div>

                <div class="field">
                    <label for="password">Password baru</label>
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

                <button type="submit" class="btn btn-primary btn-block">Simpan password baru</button>
            </form>

            {{-- Kirim ulang kode. Form terpisah; isi emailnya disalin dari kolom Email di atas saat dikirim. --}}
            <form method="POST" action="{{ route('password.email') }}" id="resendForm" style="margin-top: 16px; text-align: center;">
                @csrf
                <input type="hidden" name="email" id="resendEmail" value="{{ old('email', $email) }}">
                <span class="muted" style="font-size: 0.9rem;">Belum dapat kodenya?</span>
                <button type="submit" class="link" id="resendBtn" data-wait="{{ $wait }}"
                        style="background: none; border: 0; padding: 0; font: inherit; font-weight: 650; cursor: pointer;">Kirim ulang kode</button>
            </form>

            <p class="auth-alt">Ingat password? <a class="link" href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>

    <script>
        // Semua di bawah ini hanya tampilan; server tetap memeriksa ulang di PasswordResetController.
        (function () {
            // Kolom kode: hanya angka, maksimal 6. Setelah 6 angka lengkap, pindah ke kolom password.
            var code = document.getElementById('code');
            var pass = document.getElementById('password');
            code.addEventListener('input', function () {
                var digits = code.value.replace(/\D/g, '').slice(0, 6);
                if (digits !== code.value) { code.value = digits; }
                if (digits.length === 6 && pass) { pass.focus(); }
            });

            // Daftar syarat password menyala hijau satu per satu saat terpenuhi.
            var items = document.querySelectorAll('#pwRules li');
            var tests = {
                len: function (v) { return v.length === 8; },
                letter: function (v) { return /[A-Za-z]/.test(v); },
                num: function (v) { return /\d/.test(v); },
                sym: function (v) { return /[^A-Za-z\d\s]/.test(v); }
            };
            function check() {
                items.forEach(function (li) {
                    li.classList.toggle('is-ok', tests[li.getAttribute('data-rule')](pass.value));
                });
            }
            pass.addEventListener('input', check);
            check();

            // Tombol Kirim ulang kode: menghitung mundur sisa jeda (server menolak kalau diminta terlalu cepat).
            var btn = document.getElementById('resendBtn');
            var email = document.getElementById('email');
            var hidden = document.getElementById('resendEmail');
            var left = parseInt(btn.getAttribute('data-wait') || '0', 10);
            var label = btn.textContent;

            function tick() {
                if (left <= 0) {
                    btn.disabled = false;
                    btn.style.opacity = '';
                    btn.textContent = label;
                    return;
                }
                btn.disabled = true;
                btn.style.opacity = '0.55';
                btn.textContent = label + ' (' + left + ')';
                left--;
                setTimeout(tick, 1000);
            }
            tick();

            document.getElementById('resendForm').addEventListener('submit', function (e) {
                if (!email.value.trim()) {
                    e.preventDefault();
                    email.focus();
                    return;
                }
                hidden.value = email.value.trim();
            });
        })();
    </script>
@endsection
