<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TemplateInvoiceController;
use Illuminate\Support\Facades\Route;

// Halaman pembuka untuk semua orang. Yang sudah login juga melihatnya
// (tombolnya berubah jadi "Buka daftar invoice"), jadi tidak ada lagi lompat otomatis ke dalam akun.
Route::view('/', 'landing')->name('home');

// Masuk dan Daftar SELALU bisa dibuka, juga saat browser masih tersimpan login-nya (cookie "ingat saya" atau sesi yang belum habis).
// Dulu dua halaman ini ada di grup "guest", sehingga orang yang masih login dilempar balik ke beranda dan form-nya tidak pernah tampil.
// Kalau masih login, layout menampilkan pemberitahuan "Kamu sedang masuk sebagai ..." dengan tombol Lanjut / Keluar,
// dan AuthController mengeluarkan akun lama sebelum masuk atau daftar dengan akun yang diketik.
// Pembatasan login (5 kali salah lalu dikunci) diatur di AuthController::login().
// Untuk daftar, throttle:10,1 = maksimal 10 percobaan per menit per IP.
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login']);

Route::get('register', [AuthController::class, 'showRegister'])->name('register');
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');

// Lupa password dengan kode 6 angka: minta kode lewat email (maks 5 kali per menit per IP, dan 1x per menit per email),
// lalu isi kode + password baru. Seperti Masuk/Daftar, bisa dibuka walau browser masih login.
Route::get('lupa-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
Route::post('lupa-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:5,1')->name('password.email');
Route::get('atur-ulang-password', [PasswordResetController::class, 'showReset'])->name('password.reset');
Route::post('atur-ulang-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');

// PDF invoice untuk pelanggan (link di pesan WhatsApp). Tanpa login, tapi alamatnya ditandatangani dan kedaluwarsa
// (middleware "signed"), jadi tidak bisa ditebak atau diganti ke invoice lain. Maks 30 kali per menit per IP.
Route::get('invoice/{invoice}/lihat', [InvoiceController::class, 'publicPdf'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('invoices.public');

// Semua halaman di dalam grup ini wajib login. Kalau belum, Laravel
// mengarahkan ke route bernama "login" dan mengingat halaman yang dituju.
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('data-usaha', [BusinessProfileController::class, 'edit'])->name('business.edit');
    Route::put('data-usaha', [BusinessProfileController::class, 'update'])->name('business.update');
    Route::get('data-usaha/logo', [BusinessProfileController::class, 'logo'])->name('business.logo');

    // Template invoice: tampilan invoice + pratinjau PDF pada invoice yang dipilih (maks 60 pratinjau per menit).
    Route::get('template-invoice', [TemplateInvoiceController::class, 'edit'])->name('template.edit');
    Route::put('template-invoice', [TemplateInvoiceController::class, 'update'])->name('template.update');
    Route::get('template-invoice/pratinjau', [TemplateInvoiceController::class, 'preview'])->middleware('throttle:60,1')->name('template.preview');

    Route::get('laporan', [ReportController::class, 'index'])->name('reports.index');

    Route::get('invoices/suggest', [InvoiceController::class, 'suggest'])->name('invoices.suggest');
    Route::get('invoices/{invoice}/whatsapp', [InvoiceController::class, 'whatsapp'])->name('invoices.whatsapp');
    Route::post('invoices/{invoice}/duplikat', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::get('invoices/{invoice}/logo', [InvoiceController::class, 'logo'])->name('invoices.logo');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/cetak', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'toggleStatus'])->name('invoices.status');
    Route::resource('invoices', InvoiceController::class);
});
