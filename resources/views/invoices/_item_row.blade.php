<div class="item-row" data-row>
    <div class="field field-desc">
        <label>Deskripsi</label>
        <input type="text" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" maxlength="160" required placeholder="Contoh: Kertas A4 80gsm (1 rim)">
    </div>
    <div class="field field-qty">
        <label>Jumlah</label>
        <input type="number" name="items[{{ $index }}][qty]" value="{{ $item['qty'] ?? 1 }}" min="1" step="1" required data-qty placeholder="Contoh: 10">
    </div>
    <div class="field field-price">
        <label>Harga satuan (Rp)</label>
        <input type="number" name="items[{{ $index }}][price]" value="{{ $item['price'] ?? '' }}" min="0" step="1" required data-price placeholder="Contoh: 52000">
    </div>
    <div class="field field-line">
        <label>Jumlah harga</label>
        <output class="line-total" data-line>Rp 0</output>
    </div>
    <button type="button" class="link-btn danger" data-remove>Hapus</button>
</div>
