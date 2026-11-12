{{-- Unggah logo penagih untuk satu invoice. Dipakai di langkah 1 form invoice; membaca $invoice (boleh null) dari form. --}}
@php
    // Invoice yang sudah punya data penagih memakai logonya sendiri; invoice baru / lama mulai dari logo "Data usaha".
    $ownLogo = ($invoice?->seller_name) ? $invoice->seller_logo_path : null;
    $logoUrl = null;
    if ($ownLogo) {
        $logoUrl = route('invoices.logo', $invoice) . '?v=' . substr(md5($ownLogo), 0, 8);
    } elseif (! $invoice?->seller_name && \App\Models\BusinessProfile::stored()?->logo_path) {
        $logoUrl = route('business.logo');
    }
@endphp
<div class="field">
    <label for="logo">Logo penagih (opsional)</label>
    <div class="logo-box">
        <div class="logo-preview" id="logoPreview">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo penagih saat ini">
            @else
                <span class="muted">Belum ada logo</span>
            @endif
        </div>
        <div class="logo-controls">
            <input id="logo" type="file" name="logo" accept="image/png,image/jpeg">
            <small class="field-hint">PNG atau JPG, maksimal 1 MB. Tampil di kop atas invoice dan sebagai watermark di PDF.@if ($logoUrl && ! $ownLogo) Terisi dari Data usaha; pilih file lain untuk mengganti khusus invoice ini.@endif</small>
            @if ($logoUrl)
                <label class="check" style="margin: 8px 0 0;">
                    <input type="checkbox" name="remove_logo" value="1" {{ old('remove_logo') ? 'checked' : '' }}>
                    <span>Jangan pakai logo di invoice ini</span>
                </label>
            @endif
        </div>
    </div>
</div>
