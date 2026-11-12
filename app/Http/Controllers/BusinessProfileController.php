<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\Phone;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

            'invoice_title' => ['required', 'string', 'max:30'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:1024', 'dimensions:max_width=3000,max_height=3000'],
            'bank_name' => ['nullable', 'string', 'max:60'],
            'bank_account' => ['nullable', 'string', 'max:40', 'regex:/^[0-9\s\-]+$/'],
            'bank_holder' => ['nullable', 'string', 'max:80'],
            'footer_note' => ['nullable', 'string', 'max:200'],

            'pdf_template' => ['required', Rule::in(array_keys(BusinessProfile::TEMPLATES))],
            'pdf_font' => ['required', Rule::in(array_keys(BusinessProfile::FONTS))],
            'pdf_color' => ['required', Rule::in(array_keys(BusinessProfile::COLORS))],
        ], [
            'name.required' => 'Nama usaha wajib diisi.',
            'name.max' => 'Nama usaha maksimal 120 karakter.',
            'address.required' => 'Alamat usaha wajib diisi.',
            'address.max' => 'Alamat usaha maksimal 255 karakter.',
            'email.required' => 'Email usaha wajib diisi.',
            'email.email' => 'Format email usaha tidak valid.',
            'phone.required' => 'Telepon usaha wajib diisi.',
            'phone.regex' => 'Nomor telepon harus diawali angka 8, total 9 sampai 12 angka setelah +62, tanpa spasi atau tanda baca.',
            'invoice_title.required' => 'Judul invoice wajib diisi.',
            'invoice_title.max' => 'Judul invoice maksimal 30 karakter.',
            'logo.mimes' => 'Logo harus berupa file PNG atau JPG.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
            'logo.dimensions' => 'Ukuran gambar logo terlalu besar (maksimal 3000 x 3000 piksel).',
            'logo.uploaded' => 'Logo gagal diunggah. Coba file lain (maksimal 1 MB).',
            'bank_account.regex' => 'Nomor rekening hanya boleh berisi angka, spasi, dan tanda hubung.',
            'footer_note.max' => 'Catatan kaki maksimal 200 karakter.',
            'pdf_template.in' => 'Template tidak dikenal.',
            'pdf_font.in' => 'Font tidak dikenal.',
            'pdf_color.in' => 'Warna tidak dikenal.',
        ]);

        $data['phone'] = Phone::format($data['phone']);
        unset($data['logo']);

        $profile = BusinessProfile::stored() ?? new BusinessProfile();
        $profile->fill($data);
        $profile->user_id = Auth::id();

        // Logo: dihapus kalau kotak "Hapus logo" dicentang, diganti kalau ada file baru.
        $disk = Storage::disk('local');
        if ($profile->logo_path && ($request->boolean('remove_logo') || $request->hasFile('logo'))) {
            $disk->delete($profile->logo_path);
            $profile->logo_path = null;
        }
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $profile->logo_path = $file->storeAs('logos', Auth::id() . '-' . Str::random(8) . '.' . $file->extension(), 'local');
        }

        $profile->save();

        return redirect()->route('business.edit')->with('success', 'Data usaha dan tampilan invoice berhasil disimpan.');
    }

    /** Menampilkan logo milik akun yang sedang login (file disimpan di storage privat, bukan folder publik). */
    public function logo()
    {
        $path = BusinessProfile::stored()?->logo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /** Contoh PDF dengan pengaturan yang sudah tersimpan, memakai data karangan (tidak membuat invoice apa pun). */
    public function preview()
    {
        $items = collect([
            ['Kertas A4 80gsm (rim)', 10, 52000],
            ['Tinta printer hitam', 4, 85000],
            ['Jasa instalasi jaringan kantor', 1, 2500000],
        ])->map(fn ($row, $i) => new InvoiceItem([
            'position' => $i, 'description' => $row[0], 'qty' => $row[1], 'price' => $row[2], 'line_total' => $row[1] * $row[2],
        ]));

        $subtotal = (int) $items->sum('line_total');
        $tax = (int) round($subtotal * 11 / 100);

        $invoice = new Invoice([
            'number' => 'INV-' . now()->format('d-m-Y') . '-0001',
            'customer_name' => 'PT Sumber Makmur',
            'customer_address' => 'Jl. Veteran No. 5, Cilegon, Banten',
            'customer_email' => 'pembelian@sumbermakmur.co.id',
            'customer_phone' => '+62 812-3456-7890',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'tax_percent' => 11,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $subtotal + $tax,
            'status' => 'unpaid',
            'notes' => 'Ini hanya contoh tampilan. Data di atas bukan invoice sungguhan.',
        ]);
        $invoice->setRelation('items', $items);

        $design = BusinessProfile::design();

        return Pdf::loadView('invoices.pdf', compact('invoice', 'design'))
            ->setPaper('a4')
            ->stream('contoh-invoice.pdf');
    }
}
