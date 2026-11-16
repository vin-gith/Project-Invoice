<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Lupa password dengan KODE 6 angka (bukan tautan):
 *   1. Pengguna memasukkan email -> server membuat kode acak, menyimpannya dalam bentuk hash
 *      di tabel password_reset_tokens, lalu mengirim kodenya lewat email.
 *   2. Pengguna memasukkan email + kode + password baru -> kalau kode cocok dan belum kedaluwarsa,
 *      password diganti dan kode langsung dihapus (sekali pakai).
 *
 * Pengaman: kode berlaku 10 menit, salah 5 kali = kode dibatalkan, minta kode baru dibatasi 1x per menit
 * per email, dan semua jawaban sama untuk email terdaftar maupun tidak (tidak bisa dipakai menebak email).
 */
class PasswordResetController extends Controller
{
    private const CODE_MINUTES = 10;   // masa berlaku kode
    private const RESEND_SECONDS = 60; // jeda minta kode baru untuk email yang sama
    private const MAX_TRIES = 5;       // salah kode berapa kali sebelum kode dibatalkan

    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    /** Langkah 1: buat kode dan kirim lewat email. */
    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:120'],
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'max' => ':attribute terlalu panjang.',
        ], [
            'email' => 'Email',
        ]);

        $email = Str::lower($data['email']);

        // Jeda minta kode: satu email hanya bisa meminta lagi setelah RESEND_SECONDS. Ini mencegah halaman ini
        // dipakai untuk membanjiri kotak masuk seseorang. Dicatat sebelum mengecek akun, jadi jawabannya sama untuk semua email.
        $sendKey = 'reset-send|' . $email;
        if (RateLimiter::tooManyAttempts($sendKey, 1)) {
            return $this->toCodePage($email);
        }
        RateLimiter::hit($sendKey, self::RESEND_SECONDS);

        $user = User::where('email', $data['email'])->first();

        // Email tidak terdaftar: tidak ada yang dikirim, tapi pengguna tetap diarahkan ke halaman kode dengan pesan yang sama.
        if ($user) {
            // random_int aman untuk keperluan keamanan (rand/mt_rand tidak). str_pad menjaga nol di depan: 000042.
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            try {
                // Satu baris per email: meminta kode baru otomatis menggantikan kode lama.
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($code), 'created_at' => now()],
                );
                RateLimiter::clear('reset-tries|' . $email); // kode baru = jatah salah kode mulai dari nol

                $body = "Halo,\n\n"
                    . "Ini kode untuk mengatur ulang password akun Invoicely kamu:\n\n"
                    . "    {$code}\n\n"
                    . 'Kode berlaku ' . self::CODE_MINUTES . " menit dan hanya bisa dipakai sekali.\n"
                    . "Jangan berikan kode ini ke siapa pun, termasuk yang mengaku dari Invoicely.\n\n"
                    . "Kalau kamu tidak merasa memintanya, abaikan saja email ini. Password kamu tidak akan berubah.\n";

                Mail::raw($body, function ($message) use ($user) {
                    $message->to($user->email)->subject('Kode atur ulang password Invoicely');
                });

                // Mode uji (MAIL_MAILER=log): email tidak sungguh terkirim, jadi kodenya dicatat di storage/logs/laravel.log.
                // Tidak dilakukan di mode email asli supaya kode tidak tersimpan di file log.
                if (config('mail.default') === 'log') {
                    Log::info("[Invoicely] Kode atur ulang password untuk {$user->email}: {$code}");
                }
            } catch (\Throwable $e) {
                report($e);

                // Gagal kirim: kode dibatalkan dan jeda dibuka lagi supaya bisa dicoba ulang setelah .env diperbaiki.
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                RateLimiter::clear($sendKey);

                return back()
                    ->withErrors(['email' => 'Email belum bisa dikirim saat ini. Periksa pengaturan email (MAIL_...) di file .env, lalu coba lagi.'])
                    ->onlyInput('email');
            }
        }

        return $this->toCodePage($email);
    }

    /** Langkah 2 (tampilan): form email + kode + password baru. */
    public function showReset(): View
    {
        $email = (string) session('reset_email', '');

        return view('auth.reset-password', [
            'email' => $email,
            'minutes' => self::CODE_MINUTES,
            // Sisa detik sebelum boleh meminta kode baru (untuk tombol "Kirim ulang kode").
            'wait' => $email !== '' ? RateLimiter::availableIn('reset-send|' . Str::lower($email)) : 0,
        ]);
    }

    /** Langkah 2 (proses): periksa kode, lalu ganti password. */
    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'code' => ['required', 'digits:6'],
            // Aturan sama dengan saat daftar: tepat 8 karakter, berisi huruf, angka, dan simbol.
            'password' => [
                'required', 'string', 'min:8', 'max:8', 'confirmed',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d\s])/',
            ],
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'code.digits' => 'Kode harus 6 angka.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute terlalu panjang.',
            'password.max' => 'Password maksimal :max karakter.',
            'confirmed' => 'Konfirmasi password tidak cocok.',
            'password.regex' => 'Password harus mengandung huruf, angka, dan simbol (contoh: ! @ # ?).',
        ], [
            'email' => 'Email',
            'code' => 'Kode',
            'password' => 'Password',
        ]);

        $email = Str::lower($data['email']);
        $triesKey = 'reset-tries|' . $email;

        // Sudah salah kode 5 kali: tolak tanpa memeriksa apa pun sampai pengguna meminta kode baru.
        if (RateLimiter::tooManyAttempts($triesKey, self::MAX_TRIES)) {
            return back()
                ->withErrors(['code' => 'Terlalu banyak kode yang salah. Minta kode baru lewat tombol Kirim ulang kode.'])
                ->onlyInput('email');
        }

        $row = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        // Kode harus ada, belum lewat CODE_MINUTES, dan cocok dengan hash yang tersimpan.
        $valid = $row
            && $row->created_at
            && Carbon::parse($row->created_at)->addMinutes(self::CODE_MINUTES)->isFuture()
            && Hash::check($data['code'], $row->token);

        $user = $valid ? User::where('email', $data['email'])->first() : null;

        if (! $user) {
            RateLimiter::hit($triesKey, self::CODE_MINUTES * 60);

            // Percobaan salah ke-5: kodenya dibatalkan, jadi menebak terus tidak ada gunanya.
            if ($row && RateLimiter::tooManyAttempts($triesKey, self::MAX_TRIES)) {
                DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            }

            // Satu pesan untuk semua kemungkinan (kode salah, kedaluwarsa, atau email tidak terdaftar).
            return back()
                ->withErrors(['code' => 'Kode salah atau sudah kedaluwarsa. Periksa lagi, atau minta kode baru.'])
                ->onlyInput('email');
        }

        // Berhasil. remember_token diganti supaya cookie "ingat saya" lama di perangkat lain tidak berlaku lagi.
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete(); // kode sekali pakai
        RateLimiter::clear($triesKey);
        session()->forget('reset_email');

        event(new PasswordReset($user));

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan masuk dengan password baru.');
    }

    /** Arahkan ke halaman kode. Pesannya sama untuk email terdaftar maupun tidak. */
    private function toCodePage(string $email): RedirectResponse
    {
        // put() (bukan with()) supaya email tetap terisi walau halaman dimuat ulang.
        session()->put('reset_email', $email);

        return redirect()
            ->route('password.reset')
            ->with('status', 'Kalau email itu terdaftar, kode 6 angka sudah dikirim. Cek kotak masuk dan folder spam. Kode berlaku ' . self::CODE_MINUTES . ' menit.');
    }
}
