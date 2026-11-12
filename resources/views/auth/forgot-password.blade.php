@extends('layouts.app')

@section('title', 'Lupa password')

@section('content')
    <section class="auth-wrap">
        <div class="card auth-card reveal">
            <h1>Lupa password</h1>
            <p class="muted">Masukkan email akunmu. Kami kirim tautan untuk membuat password baru.</p>

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

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autofocus maxlength="120" autocomplete="username"
                           placeholder="Contoh: kontak@tokomajujaya.com">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Kirim tautan</button>
            </form>

            @if (config('mail.default') === 'log' && app()->isLocal())
                <p class="muted set-hint" style="margin: 14px 0 0;">Mode uji: email belum disambungkan ke SMTP, jadi tautannya ditulis ke file <strong>storage/logs/laravel.log</strong>.</p>
            @endif

            <p class="auth-alt">Ingat password? <a class="link" href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>
@endsection
