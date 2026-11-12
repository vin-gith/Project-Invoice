# Invoicely - Sistem Invoice Digital (Laravel)

Folder ini berisi file aplikasinya saja (bukan satu project Laravel utuh). Cara pasang:

```bash
# 1. Bikin project Laravel baru
composer create-project laravel/laravel invoicely
cd invoicely

# 2. Pasang paket PDF
composer require barryvdh/laravel-dompdf

# 3. Salin isi folder zip ini (app, config, database, public, resources, routes)
#    ke dalam project tadi. Kalau ditanya timpa file, pilih ya.
#    (routes/web.php bawaan Laravel memang harus tertimpa.)

# 4. Buat tabel (default database Laravel 11+ adalah SQLite, file dibuat otomatis)
php artisan migrate

# 5. Opsional: isi data contoh buat demo
php artisan db:seed --class=InvoiceSeeder

# 6. Jalankan
php artisan serve
```

Buka http://127.0.0.1:8000

Biar tanggal sesuai WIB, ubah `'timezone'` di `config/app.php` jadi `'Asia/Jakarta'`.
Data usaha di invoice dan PDF diatur di `config/invoicely.php` (atau lewat `.env`:
`INVOICELY_COMPANY`, `INVOICELY_ADDRESS`, `INVOICELY_EMAIL`, `INVOICELY_PHONE`).

## Fitur

| Fitur | Di mana |
|---|---|
| Buat dan kelola invoice (tambah, lihat, ubah, hapus, cari, filter) | `InvoiceController` |
| Nomor invoice otomatis `INV-YYYYMM-0001` | `Invoice::nextNumber()` |
| Hitung total tagihan (subtotal, pajak, total) | `Invoice::recalculate()` dan JS di form |
| Status pembayaran lunas / belum lunas | `InvoiceController::toggleStatus()` |
| Unduh invoice PDF | `InvoiceController::pdf()` + `invoices/pdf.blade.php` |
| Mode gelap dan terang | `public/css/app.css` + `public/js/app.js` |

## Catatan desain (buat bahan presentasi)

- **Nomor invoice**: dicari dari nomor terakhir bulan ini lalu ditambah satu, di dalam
  `DB::transaction`. Kolom `number` juga `unique`, jadi nomor kembar tidak mungkin lolos.
- **Total dihitung ulang di server**: browser hanya menampilkan pratinjau. Angka yang
  disimpan selalu hasil hitung server dari item yang divalidasi, jadi tidak bisa dimanipulasi
  lewat inspect element.
- **Uang disimpan sebagai integer rupiah**, bukan float, supaya tidak ada selisih pembulatan.
- **Tema**: semua warna dari CSS variables. Atribut `data-theme` di `<html>` menukar palet,
  pilihan disimpan di `localStorage`, dan default mengikuti pengaturan sistem pengguna.
- **Animasi**: transisi halaman, menu geser, form dua langkah, baris item, angka berhitung.
  Semua mati otomatis kalau perangkat mengaktifkan "reduce motion".
- Tidak ada ikon, emoji, atau logo. Status hanya memakai teks dan warna.
