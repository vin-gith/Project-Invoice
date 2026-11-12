<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Halaman pembuka untuk semua orang. Yang sudah login juga melihatnya
// (tombolnya berubah jadi "Buka daftar invoice"), jadi tidak ada lagi lompat otomatis ke dalam akun.
Route::view('/', 'landing')->name('home');

// Halaman untuk tamu (belum login). Kalau sudah login, middleware "guest"
// otomatis melempar pengguna balik ke halaman utama.
// Pembatasan login (5 kali salah lalu dikunci) diatur di AuthController::login().
// Untuk daftar, throttle:10,1 = maksimal 10 percobaan per menit per IP.
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);

    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');

    // Lupa password: minta tautan lewat email (maks 5 kali per menit per IP), lalu buat password baru lewat tautan itu.
    Route::get('lupa-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('lupa-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('atur-ulang-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('atur-ulang-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
});

// Semua halaman di dalam grup ini wajib login. Kalau belum, Laravel
// mengarahkan ke route bernama "login" dan mengingat halaman yang dituju.
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('data-usaha', [BusinessProfileController::class, 'edit'])->name('business.edit');
    Route::put('data-usaha', [BusinessProfileController::class, 'update'])->name('business.update');
    Route::get('data-usaha/logo', [BusinessProfileController::class, 'logo'])->name('business.logo');
    Route::get('data-usaha/pratinjau', [BusinessProfileController::class, 'preview'])->name('business.preview');

    Route::get('invoices/suggest', [InvoiceController::class, 'suggest'])->name('invoices.suggest');
    Route::get('invoices/{invoice}/logo', [InvoiceController::class, 'logo'])->name('invoices.logo');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/cetak', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'toggleStatus'])->name('invoices.status');
    Route::resource('invoices', InvoiceController::class);
});
