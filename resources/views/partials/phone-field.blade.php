{{-- Kolom telepon wajib dengan awalan "+62" yang tidak bisa dihapus. Parameter: $id, $name, $value (angka setelah +62, diawali 8). --}}
<div class="phone-wrap" data-phone>
    <span class="phone-prefix" aria-hidden="true">+62</span>
    <input id="{{ $id }}" type="text" name="{{ $name }}" value="{{ $value }}"
           inputmode="numeric" autocomplete="off" maxlength="12" required
           pattern="8[0-9]{8,11}" title="Mulai dengan angka 8, total 9 sampai 12 angka setelah +62"
           placeholder="81234567890" aria-label="Nomor telepon, diawali +62">
</div>
<small class="field-hint">Setelah +62 langsung angka 8, total 9 sampai 12 angka.</small>
