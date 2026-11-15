<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Data usaha = identitas saja (nama, alamat, email, telepon).
 * Tampilan invoice (judul, logo, template, font, warna, bank, catatan kaki) ada di TemplateInvoiceController.
 */
class BusinessProfileController extends Controller
{
    public function edit()
    {
        // Form hanya diisi data yang benar-benar sudah tersimpan; kalau belum ada, kolom kosong dan tampil contoh.
        return view('business.edit', ['stored' => BusinessProfile::stored()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'phone' => ['required', 'regex:/^8[0-9]{8,11}$/'],
        ], [
            'name.required' => 'Nama usaha wajib diisi.',
            'name.max' => 'Nama usaha maksimal 120 karakter.',
            'address.required' => 'Alamat usaha wajib diisi.',
            'address.max' => 'Alamat usaha maksimal 255 karakter.',
            'email.required' => 'Email usaha wajib diisi.',
            'email.email' => 'Format email usaha tidak valid.',
            'phone.required' => 'Telepon usaha wajib diisi.',
            'phone.regex' => 'Nomor telepon harus diawali angka 8, total 9 sampai 12 angka setelah +62, tanpa spasi atau tanda baca.',
        ]);

        $data['phone'] = Phone::format($data['phone']);

        // Hanya 4 kolom identitas yang disentuh, jadi pengaturan template yang sudah tersimpan tidak ikut tertimpa.
        // Kalau barisnya baru dibuat, kolom tampilan otomatis terisi nilai bawaan dari database (Modern, Sans, Biru).
        $profile = BusinessProfile::stored() ?? new BusinessProfile();
        $profile->fill($data);
        $profile->user_id = Auth::id();
        $profile->save();

        return redirect()->route('business.edit')->with('success', 'Data usaha berhasil disimpan.');
    }

    /** Menampilkan logo milik akun yang sedang login (file disimpan di storage privat, bukan folder publik). */
    public function logo()
    {
        $path = BusinessProfile::stored()?->logo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
