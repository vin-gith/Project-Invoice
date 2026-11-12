<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\LogoImage;
use App\Support\Phone;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        // Hanya invoice milik akun yang sedang login.
        $query = $this->mine()->orderByDesc('issue_date')->orderByDesc('id');

        if ($search = trim((string) $request->query('q'))) {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('number', 'like', $like)
                    ->orWhere('customer_name', 'like', $like);
            });
        }

        if (in_array($request->query('status'), ['paid', 'unpaid'], true)) {
            $query->where('status', $request->query('status'));
        }

        $invoices = $query->paginate(10)->withQueryString();

        $stats = [
            'count' => $this->mine()->count(),
            'paid_total' => (int) $this->mine()->where('status', 'paid')->sum('total'),
            'unpaid_total' => (int) $this->mine()->where('status', 'unpaid')->sum('total'),
            'unpaid_count' => $this->mine()->where('status', 'unpaid')->count(),
        ];

        return view('invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Rekomendasi pencarian: nama pelanggan dan nomor invoice milik akun ini yang cocok dengan ketikan.
     * Dipanggil lewat JavaScript saat pengguna mengetik di kolom cari.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));
        if ($term === '') {
            return response()->json([]);
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';
        $rows = $this->mine()
            ->where(function ($q) use ($like) {
                $q->where('customer_name', 'like', $like)->orWhere('number', 'like', $like);
            })
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get(['number', 'customer_name']);

        $items = [];

        // Nama pelanggan (satu kali per nama) beserta jumlah invoicenya.
        foreach ($rows->groupBy('customer_name') as $name => $group) {
            $name = (string) $name;
            if (mb_stripos($name, $term) !== false) {
                $items[] = ['value' => $name, 'hint' => $group->count() . ' invoice'];
            }
        }

        // Nomor invoice yang cocok.
        foreach ($rows as $row) {
            if (mb_stripos($row->number, $term) !== false) {
                $items[] = ['value' => $row->number, 'hint' => $row->customer_name];
            }
        }

        return response()->json(array_slice($items, 0, 6));
    }

    public function create(): View
    {
        return view('invoices.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages(), $this->attributes());
        $logoPath = $this->resolveLogo($request, null);

        $invoice = DB::transaction(function () use ($data, $logoPath) {
            $invoice = Invoice::create([
                'user_id' => Auth::id(),
                'number' => Invoice::nextNumber((int) Auth::id()),
                'seller_name' => $data['seller_name'],
                'seller_address' => $data['seller_address'],
                'seller_email' => $data['seller_email'],
                'seller_phone' => filled($data['seller_phone'] ?? null) ? Phone::format($data['seller_phone']) : null,
                'seller_logo_path' => $logoPath,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => filled($data['customer_phone'] ?? null) ? Phone::format($data['customer_phone']) : null,
                'customer_address' => $data['customer_address'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'tax_percent' => $data['tax_percent'],
                'notes' => $data['notes'] ?? null,
                'status' => 'unpaid',
            ]);

            $this->syncItems($invoice, $data['items']);

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->number} berhasil dibuat.");
    }

    public function show(Invoice $invoice): View
    {
        $this->authorizeOwner($invoice);

        $invoice->load('items');

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorizeOwner($invoice);

        $invoice->load('items');

        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeOwner($invoice);

        $data = $request->validate($this->rules(), $this->messages(), $this->attributes());
        $logoPath = $this->resolveLogo($request, $invoice);

        DB::transaction(function () use ($invoice, $data, $logoPath) {
            // Nomor dan status pembayaran sengaja tidak ikut diubah di sini.
            $invoice->update([
                'seller_name' => $data['seller_name'],
                'seller_address' => $data['seller_address'],
                'seller_email' => $data['seller_email'],
                'seller_phone' => filled($data['seller_phone'] ?? null) ? Phone::format($data['seller_phone']) : null,
                'seller_logo_path' => $logoPath,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => filled($data['customer_phone'] ?? null) ? Phone::format($data['customer_phone']) : null,
                'customer_address' => $data['customer_address'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'tax_percent' => $data['tax_percent'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($invoice, $data['items']);
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Perubahan invoice disimpan.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorizeOwner($invoice);

        $number = $invoice->number;
        $logo = $invoice->seller_logo_path;

        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });

        // File logo milik invoice ini ikut dihapus (tiap invoice punya salinan sendiri).
        if ($logo) {
            Storage::disk('local')->delete($logo);
        }

        return redirect()
            ->route('invoices.index')
            ->with('success', "Invoice {$number} dihapus.");
    }

    public function toggleStatus(Invoice $invoice): RedirectResponse
    {
        $this->authorizeOwner($invoice);

        $paying = ! $invoice->isPaid();

        $invoice->update([
            'status' => $paying ? 'paid' : 'unpaid',
            'paid_at' => $paying ? now() : null,
        ]);

        return back()->with(
            'success',
            $paying ? "{$invoice->number} ditandai lunas." : "{$invoice->number} ditandai belum lunas."
        );
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorizeOwner($invoice);

        return $this->makePdf($invoice)->download($invoice->number . '.pdf');
    }

    /**
     * Cetak: PDF dibuka di tab baru (tidak diunduh), lalu dicetak lewat tombol print di penampil PDF browser
     * atau Ctrl+P. Pilihan printer (termasuk "Save as PDF") ada di jendela cetak milik browser/Windows.
     */
    public function print(Invoice $invoice)
    {
        $this->authorizeOwner($invoice);

        return $this->makePdf($invoice)->stream($invoice->number . '.pdf');
    }

    /** Menampilkan logo penagih milik invoice ini (untuk pratinjau di form ubah invoice). */
    public function logo(Invoice $invoice)
    {
        $this->authorizeOwner($invoice);

        $path = $invoice->sellerLogoPath();
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function makePdf(Invoice $invoice)
    {
        $invoice->load('items');
        $design = BusinessProfile::design();

        // Logo di kop dan watermark memakai logo milik invoice ini, bukan logo "Data usaha" saat ini.
        $logoPath = $invoice->sellerLogoPath();
        $design['logo'] = LogoImage::dataUri($logoPath);
        $design['watermark'] = LogoImage::watermark($logoPath);

        return Pdf::loadView('invoices.pdf', compact('invoice', 'design'))->setPaper('a4');
    }

    /**
     * Logo penagih untuk satu invoice: file baru > kotak "hapus logo" > tidak diubah.
     * Kalau tidak diubah, invoice baru (atau invoice lama yang belum punya data penagih) menyalin
     * logo dari "Data usaha", jadi mengganti logo usaha nanti tidak ikut mengubah invoice ini.
     */
    private function resolveLogo(Request $request, ?Invoice $invoice): ?string
    {
        $disk = Storage::disk('local');
        $hasSnapshot = (bool) $invoice?->seller_name;
        $own = $hasSnapshot ? $invoice->seller_logo_path : null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->storeAs('invoice-logos', Auth::id() . '-' . Str::random(10) . '.' . $file->extension(), 'local');
            if ($own) {
                $disk->delete($own);
            }

            return $path;
        }

        if ($request->boolean('remove_logo')) {
            if ($own) {
                $disk->delete($own);
            }

            return null;
        }

        if ($hasSnapshot) {
            return $own;
        }

        $source = BusinessProfile::stored()?->logo_path;
        if ($source && $disk->exists($source)) {
            $copy = 'invoice-logos/' . Auth::id() . '-' . Str::random(10) . '.' . (pathinfo($source, PATHINFO_EXTENSION) ?: 'png');
            $disk->copy($source, $copy);

            return $copy;
        }

        return null;
    }

    /** Invoice milik akun yang sedang login saja. */
    private function mine()
    {
        return Invoice::where('user_id', Auth::id());
    }

    /** Invoice milik akun lain dijawab 404 (seolah tidak ada), bukan 403, supaya keberadaannya tidak bocor. */
    private function authorizeOwner(Invoice $invoice): void
    {
        abort_unless((int) $invoice->user_id === (int) Auth::id(), 404);
    }

    /** Ganti seluruh item invoice dengan data dari form, lalu hitung ulang total. */
    private function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        $rows = [];
        foreach (array_values($items) as $position => $item) {
            $rows[] = new InvoiceItem([
                'position' => $position,
                'description' => $item['description'],
                'qty' => (int) $item['qty'],
                'price' => (int) $item['price'],
                'line_total' => (int) $item['qty'] * (int) $item['price'],
            ]);
        }

        $invoice->items()->saveMany($rows);
        $invoice->recalculate();
    }

    private function rules(): array
    {
        return [
            'seller_name' => ['required', 'string', 'max:120'],
            'seller_address' => ['required', 'string', 'max:255'],
            'seller_email' => ['required', 'email:rfc', 'max:120'],
            'seller_phone' => ['required', 'regex:/^8[0-9]{8,11}$/'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:1024', 'dimensions:max_width=3000,max_height=3000'],
            'remove_logo' => ['nullable', 'boolean'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'customer_phone' => ['required', 'regex:/^8[0-9]{8,11}$/'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:160'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.price' => ['required', 'integer', 'min:0', 'max:100000000000'],
        ];
    }

    private function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'integer' => ':attribute harus berupa angka bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'min' => ':attribute terlalu kecil.',
            'max' => ':attribute terlalu besar atau terlalu panjang.',
            'customer_phone.regex' => 'Nomor telepon harus diawali angka 8, total 9 sampai 12 angka setelah +62, tanpa spasi atau tanda baca.',
            'seller_phone.regex' => 'Nomor telepon harus diawali angka 8, total 9 sampai 12 angka setelah +62, tanpa spasi atau tanda baca.',
            'logo.mimes' => 'Logo harus berupa file PNG atau JPG.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
            'logo.dimensions' => 'Ukuran gambar logo terlalu besar (maksimal 3000 x 3000 piksel).',
            'logo.uploaded' => 'Logo gagal diunggah. Coba file lain (maksimal 1 MB).',
            'after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal terbit.',
            'items.required' => 'Tambahkan minimal satu item.',
            'items.min' => 'Tambahkan minimal satu item.',
        ];
    }

    private function attributes(): array
    {
        return [
            'seller_name' => 'Nama penagih',
            'seller_address' => 'Alamat penagih',
            'seller_email' => 'Email penagih',
            'seller_phone' => 'Telepon penagih',
            'logo' => 'Logo penagih',
            'customer_name' => 'Nama pelanggan',
            'customer_email' => 'Email',
            'customer_phone' => 'Telepon pelanggan',
            'customer_address' => 'Alamat',
            'issue_date' => 'Tanggal terbit',
            'due_date' => 'Jatuh tempo',
            'tax_percent' => 'Pajak',
            'notes' => 'Catatan',
            'items.*.description' => 'Deskripsi item',
            'items.*.qty' => 'Jumlah item',
            'items.*.price' => 'Harga item',
        ];
    }
}
