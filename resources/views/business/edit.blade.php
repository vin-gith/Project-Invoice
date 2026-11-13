@extends('layouts.app')

@use('App\Models\BusinessProfile')

@section('title', 'Data Usaha')

@php
    $v = fn (string $key, $default = '') => old($key, $stored?->{$key} ?? $default);
    $tpl = $v('pdf_template', 'modern');
    $col = $v('pdf_color', 'biru');
@endphp

@section('content')
    <section class="page-head reveal">
        <div>
            <h1>Data usaha</h1>
            <p class="muted">Identitas usaha dan tampilan invoice. Dipakai di halaman invoice, PDF, dan saat dicetak.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">Kembali ke daftar</a>
    </section>

    <div class="card reveal" style="--i:1; max-width: 760px;">
        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('business.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- ====== 1. Identitas (kop invoice) ====== --}}
            <h3 class="set-title">Identitas usaha</h3>
            <p class="muted set-hint">Nama dan alamat ini menjadi kop di bagian atas invoice. Untuk satu invoice tertentu, isinya masih bisa diganti di langkah "Data penagih".</p>

            <div class="field">
                <label for="name">Nama usaha</label>
                <input id="name" type="text" name="name" value="{{ old('name', $stored?->name) }}" required maxlength="120" placeholder="Contoh: Toko Maju Jaya">
            </div>

            <div class="field">
                <label for="address">Alamat usaha</label>
                <input id="address" type="text" name="address" value="{{ old('address', $stored?->address) }}" required maxlength="255" placeholder="Contoh: Jl. Merdeka No. 12, Serang, Banten">
            </div>

            <div class="grid-2">
                <div class="field">
                    <label for="email">Email usaha</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $stored?->email) }}" required maxlength="120" placeholder="Contoh: kontak@tokomajujaya.com">
                </div>
                <div class="field">
                    <label for="phone">Telepon</label>
                    @include('partials.phone-field', [
                        'id' => 'phone',
                        'name' => 'phone',
                        'value' => old('phone', \App\Support\Phone::rest($stored?->phone)),
                    ])
                </div>
            </div>

            {{-- ====== 2. Judul dan logo ====== --}}
            <h3 class="set-title">Judul dan logo</h3>

            <div class="field">
                <label for="invoice_title">Judul invoice</label>
                <input id="invoice_title" type="text" name="invoice_title" value="{{ $v('invoice_title', 'INVOICE') }}" required maxlength="30" placeholder="Contoh: INVOICE, FAKTUR, atau NOTA PENJUALAN">
                <small class="field-hint">Tampil sebagai judul besar di invoice dan PDF.</small>
            </div>

            <div class="field">
                <label for="logo">Logo usaha (opsional)</label>
                <div class="logo-box">
                    <div class="logo-preview" id="logoPreview">
                        @if ($stored?->logo_path)
                            <img src="{{ route('business.logo') }}?v={{ $stored->updated_at?->timestamp }}" alt="Logo usaha saat ini">
                        @else
                            <span class="muted">Belum ada logo</span>
                        @endif
                    </div>
                    <div class="logo-controls">
                        <input id="logo" type="file" name="logo" accept="image/png,image/jpeg">
                        <small class="field-hint">PNG atau JPG, maksimal 1 MB. Logo berlatar transparan (PNG) paling bagus.</small>
                        @if ($stored?->logo_path)
                            <label class="check" style="margin: 8px 0 0;">
                                <input type="checkbox" name="remove_logo" value="1">
                                <span>Hapus logo yang sekarang</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ====== 3. Tampilan PDF ====== --}}
            <h3 class="set-title">Tampilan invoice dan PDF</h3>

            <div class="field">
                <label>Template</label>
                <div class="opt-grid">
                    @foreach (BusinessProfile::TEMPLATES as $code => [$label, $desc])
                        <label class="opt">
                            <input type="radio" name="pdf_template" value="{{ $code }}" {{ $tpl === $code ? 'checked' : '' }}>
                            <span class="thumb thumb-{{ $code }}" aria-hidden="true">
                                <i></i><i></i><i></i><i></i>
                            </span>
                            <strong>{{ $label }}</strong>
                            <small>{{ $desc }}</small>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label for="pdf_font">Font</label>
                    <select id="pdf_font" name="pdf_font">
                        @foreach (BusinessProfile::FONTS as $code => $font)
                            <option value="{{ $code }}" {{ $v('pdf_font', 'sans') === $code ? 'selected' : '' }} style="font-family: {!! $font[2] !!}">{{ $font[0] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Warna aksen</label>
                    <div class="swatches">
                        @foreach (BusinessProfile::COLORS as $code => [$label, $hex])
                            <label class="swatch" title="{{ $label }}" style="--sw: {{ $hex }}">
                                <input type="radio" name="pdf_color" value="{{ $code }}" {{ $col === $code ? 'checked' : '' }}>
                                <span class="dot"></span>
                                <span class="swatch-name">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ====== 4. Pembayaran dan catatan ====== --}}
            <h3 class="set-title">Informasi pembayaran</h3>
            <p class="muted set-hint">Opsional. Kalau diisi, tampil di invoice supaya pelanggan tahu ke mana harus membayar.</p>

            <div class="grid-2">
                <div class="field">
                    <label for="bank_name">Nama bank</label>
                    <input id="bank_name" type="text" name="bank_name" value="{{ $v('bank_name') }}" maxlength="60" placeholder="Contoh: BCA">
                </div>
                <div class="field">
                    <label for="bank_account">Nomor rekening</label>
                    <input id="bank_account" type="text" name="bank_account" value="{{ $v('bank_account') }}" maxlength="40" inputmode="numeric" placeholder="Contoh: 1234567890">
                </div>
            </div>

            <div class="field">
                <label for="bank_holder">Atas nama</label>
                <input id="bank_holder" type="text" name="bank_holder" value="{{ $v('bank_holder') }}" maxlength="80" placeholder="Contoh: Toko Maju Jaya">
            </div>

            <div class="field">
                <label for="footer_note">Catatan (opsional)</label>
                <input id="footer_note" type="text" name="footer_note" value="{{ $v('footer_note') }}" maxlength="200" placeholder="Contoh: Terima kasih atas kepercayaan Anda.">
            </div>

            <div class="set-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a class="btn btn-ghost" href="{{ route('business.preview') }}" target="_blank" rel="noopener" data-no-transition>Lihat contoh PDF</a>
            </div>
            <p class="muted set-hint" style="margin: 10px 0 0;">Contoh PDF memakai pengaturan yang sudah tersimpan, jadi tekan Simpan dulu kalau baru mengubah sesuatu.</p>
        </form>
    </div>
@endsection
