# Invoicely - Sistem Invoice Digital (Laravel)

Aplikasi invoice untuk usaha kecil: buat invoice, hitung total otomatis, catat status pembayaran,
dan unduh atau cetak PDF. Setiap akun punya daftar invoice dan data usaha sendiri.

Dibuat dengan Laravel 12 (PHP 8.2+), MySQL, dan `barryvdh/laravel-dompdf` untuk PDF.
Tampilan memakai Blade, CSS, dan JavaScript biasa di folder `public/` (tanpa framework frontend).

## Cara menjalankan

```bash
# 1. Pasang paket PHP
composer install

# 2. Salin pengaturan, lalu buat APP_KEY
cp .env.example .env
php artisan key:generate

# 3. Buat database "Invoicely" di MySQL (lihat DB_... di .env), lalu buat tabelnya
php artisan migrate

# 4. Opsional: isi data contoh buat demo
php artisan db:seed --class=InvoiceSeeder

# 5. Jalankan
php artisan serve
```

Buka http://127.0.0.1:8000

- Zona waktu sudah `Asia/Jakarta` (di `config/app.php`).
- Data usaha bawaan (dipakai kalau akun belum mengisi menu "Data usaha") ada di `config/invoicely.php`,
  atau lewat `.env`: `INVOICELY_COMPANY`, `INVOICELY_ADDRESS`, `INVOICELY_EMAIL`, `INVOICELY_PHONE`.
- Lupa password mengirim kode lewat email. Untuk uji coba tanpa email asli, pakai `MAIL_MAILER=log`:
  kodenya muncul di `storage/logs/laravel.log`.
- Pemeriksaan domain email saat daftar butuh internet. Untuk demo offline, tambahkan
  `'check_email_dns' => false` di `config/invoicely.php`.
- Kalau dibuka lewat tunnel (ngrok / serveo), header proxy hanya dipercaya dari `127.0.0.1` dan `::1`
  (lihat `bootstrap/app.php`). Kalau dipasang di belakang proxy lain, tambahkan IP proxy itu di sana.

## Fitur

| Fitur | Di mana |
|---|---|
| Daftar, masuk, keluar (login dikunci 1 menit setelah 5 kali salah) | `AuthController` |
| Lupa password dengan kode 6 angka lewat email | `PasswordResetController` |
| Buat dan kelola invoice (tambah, lihat, ubah, hapus, cari dengan saran, filter status) | `InvoiceController` |
| Nomor invoice otomatis `INV-DD-MM-YYYY-0001`, urutan lanjut terus per akun | `Invoice::nextNumber()` |
| Hitung total tagihan (subtotal, pajak, total) | `Invoice::recalculate()` dan JS di form |
| Status pembayaran lunas / belum lunas / terlambat | `InvoiceController::toggleStatus()`, `Invoice::statusKey()` |
| Data usaha per akun (nama, alamat, email, telepon) | `BusinessProfileController` |
| Template PDF (Modern, Klasik, Minimalist), font, warna, logo, info bank, catatan kaki, pratinjau | `TemplateInvoiceController`, `BusinessProfile::design()` |
| Unduh dan cetak invoice PDF, dengan terbilang rupiah dan watermark logo | `InvoicePdf`, `Terbilang`, `LogoImage` |
| Rapikan ulang nomor invoice lama: `php artisan invoices:renumber` | `RenumberInvoices` |
| Mode gelap dan terang | `public/css/app.css`, `public/css/glass.css`, `public/js/app.js` |

## Catatan desain (buat bahan presentasi)

- **Satu akun, satu daftar**: invoice dan data usaha punya kolom `user_id`. Invoice milik akun lain
  dijawab 404 (seolah tidak ada), supaya keberadaannya tidak bocor.
- **Nomor invoice**: diambil dari angka urutan terbesar milik akun itu lalu ditambah satu, di dalam
  `DB::transaction` dengan `lockForUpdate`. Pasangan `user_id` + `number` juga `unique`, jadi nomor kembar
  tidak mungkin lolos.
- **Total dihitung ulang di server**: browser hanya menampilkan pratinjau. Angka yang disimpan selalu
  hasil hitung server dari item yang divalidasi, jadi tidak bisa dimanipulasi lewat inspect element.
- **Uang disimpan sebagai integer rupiah**, bukan float, supaya tidak ada selisih pembulatan.
- **Data penagih dan logo disalin ke tiap invoice**: mengganti data usaha nanti tidak mengubah invoice
  yang sudah dibuat.
- **Password**: 8 sampai 72 karakter, wajib berisi huruf, angka, dan simbol. Disimpan sebagai hash bcrypt.
- **Tema**: semua warna dari CSS variables. Atribut `data-theme` di `<html>` menukar palet, pilihan disimpan
  di `localStorage`, dan pertama kali dibuka selalu mode terang.
- **Animasi**: transisi halaman, menu geser, form dua langkah, baris item, angka berhitung.
  Semua mati otomatis kalau perangkat mengaktifkan "reduce motion".
- Tidak ada ikon atau emoji. Status hanya memakai teks dan warna. Logo yang tampil hanya logo usaha
  yang diunggah pengguna.
