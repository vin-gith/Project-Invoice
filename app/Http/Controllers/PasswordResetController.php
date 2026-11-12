<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Lupa password: pengguna memasukkan email, server membuat token sekali pakai (disimpan dalam
 * bentuk hash di tabel password_reset_tokens), lalu mengirim tautan berisi token itu lewat email.
 * Tautan berlaku 60 menit (config/auth.php) dan hanya bisa dipakai sekali.
 */
class PasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
        ], [
            'email' => 'Email',
        ]);

        $minutes = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        try {
            // Kalau email terdaftar, fungsi di bawah dipanggil dengan user dan token barunya.
            // Kalau tidak terdaftar, tidak terjadi apa-apa (dan jawaban untuk pengguna tetap sama).
            Password::broker()->sendResetLink($request->only('email'), function ($user, $token) use ($minutes) {
                $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);

                $body = "Halo,\n\n"
                    . "Kami menerima permintaan untuk mengatur ulang password akun Invoicely dengan email ini.\n\n"
                    . "Buka tautan berikut untuk membuat password baru (berlaku {$minutes} menit, hanya bisa dipakai sekali):\n"
                    . "{$url}\n\n"
                    . "Kalau kamu tidak merasa memintanya, abaikan saja email ini. Password kamu tidak akan berubah.\n";

                Mail::raw($body, function ($message) use ($user) {
                    $message->to($user->getEmailForPasswordReset())->subject('Atur ulang password Invoicely');
                });

                // Mode uji (MAIL_MAILER=log): isi email ditulis ke storage/logs/laravel.log dengan penyandian
                // yang membuat tautan sulit dibaca, jadi tautan polosnya dicatat juga. Tidak dilakukan di mode email asli.
                if (config('mail.default') === 'log') {
                    Log::info("[Invoicely] Tautan atur ulang password untuk {$user->getEmailForPasswordReset()}: {$url}");
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['email' => 'Email belum bisa dikirim saat ini. Periksa pengaturan email (MAIL_...) di file .env, lalu coba lagi.'])
                ->onlyInput('email');
        }

        // Sengaja satu jawaban untuk semua kasus supaya orang luar tidak bisa menebak email mana yang terdaftar.
        return back()->with('status', "Kalau email itu terdaftar, tautan untuk mengatur ulang password sudah dikirim. Cek kotak masuk dan folder spam. Tautan berlaku {$minutes} menit.");
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            // Aturan sama dengan saat daftar: tepat 8 karakter, berisi huruf, angka, dan simbol.
            'password' => [
                'required', 'string', 'min:8', 'max:8', 'confirmed',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d\s])/',
            ],pro
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute terlalu panjang.',
            'confirmed' => 'Konfirmasi password tidak cocok.',
            'password.max' => 'Password maksimal :max karakter.',
            'password.regex' => 'Password harus mengandung huruf, angka, dan simbol (contoh: ! @ # ?).',
        ], [
            'email' => 'Email',
            'password' => 'Password',
            'token' => 'Tautan',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                // remember_token diganti supaya perangkat yang masih "diingat" dengan password lama ikut keluar.
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan masuk dengan password baru.');
        }

        return back()
            ->withErrors(['email' => 'Tautan tidak valid atau sudah kedaluwarsa. Minta tautan baru lewat "Lupa password?".'])
            ->onlyInput('email');
    }
}
