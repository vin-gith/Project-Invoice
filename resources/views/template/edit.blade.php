@extends('layouts.app')

@use('App\Models\BusinessProfile')
@use('App\Models\Invoice')

@section('title', 'Template Invoice')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/template.css') }}">
@endpush

@php
    $v = fn (string $key, $default = '') => old($key, $stored?->{$key} ?? $default);
    $tpl = $v('pdf_template', 'modern');
    $col = $v('pdf_color', 'biru');
    $biz = BusinessProfile::current();
@endphp

@section('content')
    <section class="page-head reveal">
        <div>
            <h1>Template invoice</h1>
            <p class="muted">Atur tampilan invoice, lalu coba langsung pada salah satu invoice di akun ini. Template yang disimpan dipakai di PDF, unduhan, dan cetak.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('invoices.index') }}">Kembali ke daftar</a>
    </section>

    <div class="tpl-layout">
        {{-- ====== Kiri: pengaturan tampilan (dulu ada di Data usaha) ====== --}}
        <div class="card reveal tpl-settings" style="--i:1;">
            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @unless ($stored)
                <div class="alert alert-note">
                    Data usaha belum disimpan, jadi pratinjau memakai data contoh dan template belum bisa disimpan.
                    <a href="{{ route('business.edit') }}">Isi Data usaha dulu</a>.
                </div>
            @endunless

            <form id="tplForm" method="POST" action="{{ route('template.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="invoice" id="tplInvoiceField" value="{{ $selected }}">

                <div class="tpl-biz">
                    <span class="tpl-biz-label">Data usaha</span>
                    <strong translate="no">{{ $biz->name }}</strong>
                    <span class="muted" translate="no">{{ $biz->address }}</span>
                    <span class="muted" translate="no">{{ $biz->email }} | {{ $biz->phone }}</span>
                    <small class="muted">Kop invoice memakai data penagih di tiap invoice, yang awalnya disalin dari sini. <a href="{{ route('business.edit') }}">Ubah Data usaha</a></small>
                </div>

                {{-- ====== 1. Judul dan logo ====== --}}
                <h3 class="set-title">Judul dan logo</h3>

                <div class="field">
                    <label for="invoice_title">Judul invoice</label>
                    <input id="invoice_title" type="text" name="invoice_title" value="{{ $v('invoice_title', 'INVOICE') }}" required maxlength="30">
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
                            <small class="field-hint">PNG atau JPG, maksimal 1 MB. Logo berlatar transparan (PNG) paling bagus. Logo baru ikut tampil di pratinjau setelah disimpan.</small>
                            @if ($stored?->logo_path)
                                <label class="check" style="margin: 8px 0 0;">
                                    <input type="checkbox" name="remove_logo" value="1">
                                    <span>Hapus logo yang sekarang</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ====== 2. Template, font, warna ====== --}}
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

                {{-- ====== 3. Pembayaran dan catatan kaki ====== --}}
                <h3 class="set-title">Informasi pembayaran</h3>
                <p class="muted set-hint">Opsional. Kalau diisi, tampil di invoice supaya pelanggan tahu ke mana harus membayar.</p>

                <div class="grid-2">
                    <div class="field">
                        <label for="bank_name">Nama bank</label>
                        <input id="bank_name" type="text" name="bank_name" value="{{ $v('bank_name') }}" maxlength="60">
                    </div>
                    <div class="field">
                        <label for="bank_account">Nomor rekening</label>
                        <input id="bank_account" type="text" name="bank_account" value="{{ $v('bank_account') }}" maxlength="40" inputmode="numeric">
                    </div>
                </div>

                <div class="field">
                    <label for="bank_holder">Atas nama</label>
                    <input id="bank_holder" type="text" name="bank_holder" value="{{ $v('bank_holder') }}" maxlength="80">
                </div>

                <div class="field">
                    <label for="footer_note">Catatan (opsional)</label>
                    <input id="footer_note" type="text" name="footer_note" value="{{ $v('footer_note') }}" maxlength="200">
                </div>

                <div class="set-actions">
                    <button type="submit" class="btn btn-primary">Simpan template</button>
                </div>
                <p class="muted set-hint" style="margin: 10px 0 0;">Pilihan di atas langsung dicoba di pratinjau tanpa disimpan. Tekan Simpan template kalau sudah cocok.</p>
            </form>
        </div>

        {{-- ====== Kanan: pilih invoice dan lihat hasilnya ====== --}}
        <aside class="card reveal tpl-preview" style="--i:2;">
            <h3 class="set-title">Coba pada invoice</h3>

            <div class="field" style="margin-bottom: 10px;">
                <div class="tpl-label-row">
                    <label for="tplInvoice">Pilih invoice</label>
                    <span class="tpl-chip" id="tplDirty" hidden>Belum disimpan</span>
                </div>
                <select id="tplInvoice" translate="no">
                    <option value="" data-meta="Data karangan, bukan invoice sungguhan.">Contoh invoice (data karangan)</option>
                    @foreach ($invoices as $inv)
                        <option value="{{ $inv->id }}"
                                data-meta="{{ $inv->customer_name }} - {{ Invoice::rupiah($inv->total) }} - {{ $inv->statusLabel() }}"
                                {{ (int) $selected === (int) $inv->id ? 'selected' : '' }}>{{ $inv->number }} - {{ $inv->customer_name }}</option>
                    @endforeach
                </select>
                <small class="field-hint" id="tplMeta" translate="no"></small>
            </div>

            <div class="tpl-stage" id="tplStage">
                <iframe id="tplFrame" class="tpl-frame" title="Pratinjau PDF invoice" data-url="{{ route('template.preview') }}"></iframe>
                <div class="tpl-loading" aria-hidden="true">
                    <span class="tpl-bar"></span>
                    <span>Menyiapkan pratinjau</span>
                </div>
            </div>

            <div class="tpl-tools">
                <button type="button" class="btn btn-ghost btn-sm" id="tplRefresh">Perbarui</button>
                <a class="btn btn-ghost btn-sm" id="tplOpen" href="{{ route('template.preview', ['invoice' => $selected]) }}" target="_blank" rel="noopener" data-no-transition>Buka dan cetak</a>
                <a class="btn btn-primary btn-sm" id="tplDownload" href="{{ route('template.preview', ['invoice' => $selected, 'unduh' => 1]) }}" data-no-transition>Unduh PDF</a>
            </div>
            <p class="muted set-hint" style="margin: 12px 0 0;">Pratinjau mengikuti pilihan di sebelah, termasuk yang belum disimpan. Kalau pratinjau tidak tampil di browser ini, pakai Buka dan cetak.</p>
        </aside>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/template.js') }}"></script>
@endpush
