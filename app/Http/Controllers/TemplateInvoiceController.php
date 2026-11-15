<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Support\InvoicePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Menu "Template invoice": mengatur tampilan invoice (judul, logo, template, font, warna, bank, catatan kaki)
 * dan mencobanya langsung pada salah satu invoice milik akun ini lewat pratinjau PDF.
 */
class TemplateInvoiceController extends Controller
{
    public function edit(Request $request)
    {
        // Hanya invoice milik akun yang sedang login. 200 terbaru sudah lebih dari cukup untuk sebuah select.
        $invoices = Invoice::where('user_id', Auth::id())
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        // Invoice yang terpilih di awal: dari ?invoice=ID kalau valid, kalau tidak invoice terbaru.
        // Kosong (null) artinya pakai invoice contoh karangan.
        $wanted = (int) $request->query('invoice');
        $selected = $invoices->contains('id', $wanted) ? $wanted : $invoices->first()?->id;

        return view('template.edit', [
            'stored' => BusinessProfile::stored(),
            'invoices' => $invoices,
            'selected' => $selected,
        ]);
    }

    public function update(Request $request)
    {
        $profile = BusinessProfile::stored();

        // Baris data usaha dibuat di halaman "Data usaha" (nama, alamat, email, telepon wajib), jadi harus ada dulu.
        if (! $profile) {
            return back()->withInput()->withErrors([
                'profile' => 'Isi dan simpan Data usaha dulu, baru template bisa disimpan. Sambil menunggu, pratinjau tetap bisa dicoba.',
            ]);
        }

        $data = $request->validate([
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

        unset($data['logo']);
        $profile->fill($data);

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

        return redirect()
            ->route('template.edit', ['invoice' => (int) $request->input('invoice') ?: null])
            ->with('success', 'Template invoice berhasil disimpan.');
    }

    /**
     * PDF pratinjau. Memakai invoice yang dipilih (?invoice=ID, harus milik akun ini) atau invoice contoh,
     * dengan pengaturan tampilan dari query string (yang belum disimpan) menimpa yang tersimpan.
     * ?unduh=1 mengunduh file, selain itu dibuka di tempat (iframe pratinjau atau tab baru untuk dicetak).
     */
    public function preview(Request $request)
    {
        $invoice = null;
        if ($id = (int) $request->query('invoice')) {
            // Invoice milik akun lain dijawab 404, sama seperti di InvoiceController.
            $invoice = Invoice::where('user_id', Auth::id())->findOrFail($id);
        }
        $invoice ??= InvoicePdf::sample();

        $pdf = InvoicePdf::make($invoice, $this->overrides($request));
        $name = ($invoice->exists ? $invoice->number : 'contoh-invoice') . '.pdf';

        return $request->boolean('unduh') ? $pdf->download($name) : $pdf->stream($name);
    }

    /**
     * Pengaturan sementara dari query string. Hanya kolom yang dikenal dan nilai yang lolos validasi yang dipakai;
     * yang tidak valid diabaikan (jatuh ke pengaturan tersimpan), bukan menampilkan error di dalam iframe.
     */
    private function overrides(Request $request): array
    {
        $validator = Validator::make($request->query(), [
            'invoice_title' => ['nullable', 'string', 'max:30'],
            'pdf_template' => ['nullable', Rule::in(array_keys(BusinessProfile::TEMPLATES))],
            'pdf_font' => ['nullable', Rule::in(array_keys(BusinessProfile::FONTS))],
            'pdf_color' => ['nullable', Rule::in(array_keys(BusinessProfile::COLORS))],
            'bank_name' => ['nullable', 'string', 'max:60'],
            'bank_account' => ['nullable', 'string', 'max:40'],
            'bank_holder' => ['nullable', 'string', 'max:80'],
            'footer_note' => ['nullable', 'string', 'max:200'],
        ]);

        $invalid = $validator->errors()->keys();
        $clean = [];
        foreach (BusinessProfile::DESIGN_FIELDS as $field) {
            if ($request->query->has($field) && ! in_array($field, $invalid, true)) {
                $clean[$field] = (string) $request->query($field);
            }
        }

        return $clean;
    }
}
