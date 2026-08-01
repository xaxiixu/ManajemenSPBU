<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use App\Models\JurnalDetail;
use App\Models\MasterBbm;
use App\Models\TangkiBbm;
use App\Services\JurnalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CoaController extends Controller
{
    // Prefix kode akun & posisi normal mengikuti kategori (aturan akuntansi tetap, tidak bisa dipilih manual)
    private const KATEGORI_INFO = [
        'aset'       => ['prefix' => '1', 'posisi' => 'debit'],
        'kewajiban'  => ['prefix' => '2', 'posisi' => 'kredit'],
        'modal'      => ['prefix' => '3', 'posisi' => 'kredit'],
        'pendapatan' => ['prefix' => '4', 'posisi' => 'kredit'],
        'beban'      => ['prefix' => '5', 'posisi' => 'debit'],
    ];

    // Sumber jurnal yang berarti "akun ini sudah pernah punya saldo awal" -
    // baik lewat halaman Saldo Awal lama (Kas + 3 tangki sekaligus) maupun
    // lewat form COA (satu akun per jurnal, mekanisme baru).
    private const SUMBER_SALDO_AWAL = ['saldo_awal', 'saldo_awal_akun'];

    public function index()
    {
        // Ambil hanya parent (parent_id null), child di-load via relasi
        $data = Coa::whereNull('parent_id')
            ->with('children')
            ->orderBy('kode_akun')
            ->get()
            ->groupBy('kategori');

        return view('coa.index', compact('data'));
    }

    public function create()
    {
        $this->authorizeManager();

        $parents = Coa::whereNull('parent_id')->where('is_aktif', 1)->orderBy('kode_akun')->get();

        // Suffix child yang sudah terpakai per parent_id, dipakai view untuk hint "suffix berikutnya"
        $childrenByParent = Coa::whereNotNull('parent_id')
            ->get(['parent_id', 'kode_akun'])
            ->groupBy('parent_id')
            ->map(fn ($group) => $group->map(fn ($c) => Str::after($c->kode_akun, '-'))->values());

        return view('coa.create', compact('parents', 'childrenByParent'));
    }

    public function store(Request $request)
    {
        $this->authorizeManager();

        $validated = $this->validateForm($request, ['saldo_awal' => 'nullable|numeric|min:0']);
        $parent    = $this->resolveParent($validated['parent_id'] ?? null);
        $kategori  = $parent ? $parent->kategori : $validated['kategori'];
        $kodeAkun  = $this->buildKodeAkun($kategori, $validated['kode_suffix'], $parent);

        if (Coa::where('kode_akun', $kodeAkun)->exists()) {
            return back()
                ->withErrors(['kode_suffix' => "Kode akun {$kodeAkun} sudah digunakan."])
                ->withInput();
        }

        // Saldo Awal hanya berlaku untuk akun neraca (aset/kewajiban/modal) -
        // form hanya mengirim field ini untuk kategori tsb (lihat JS di
        // coa/create.blade.php), tapi kita jaga juga di sini kalau-kalau
        // request dikirim manual di luar form.
        $saldoAwal = in_array($kategori, ['aset', 'kewajiban', 'modal'], true)
            ? (float) ($validated['saldo_awal'] ?? 0)
            : 0.0;

        DB::transaction(function () use ($validated, $parent, $kategori, $kodeAkun, $saldoAwal) {
            $coa = Coa::create([
                'kode_akun'     => $kodeAkun,
                'parent_id'     => $parent?->id,
                'nama_akun'     => $validated['nama_akun'],
                'kategori'      => $kategori,
                'posisi_normal' => self::KATEGORI_INFO[$kategori]['posisi'],
                'deskripsi'     => $validated['deskripsi'] ?? null,
                'saldo_awal'    => $saldoAwal > 0 ? $saldoAwal : null,
                'is_aktif'      => 1,
            ]);

            if ($saldoAwal > 0) {
                JurnalService::dariSaldoAwalAkun($coa);
            }
        });

        return redirect()->route('coa.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(Coa $coa)
    {
        $this->authorizeManager();

        // Akun berformat "1101" (standalone/parent) buang 1 digit prefix kategori,
        // akun berformat "1104-1" (child) ambil bagian setelah tanda "-"
        $kodeSuffix = $coa->parent_id
            ? Str::after($coa->kode_akun, '-')
            : substr($coa->kode_akun, 1);

        $parents = Coa::whereNull('parent_id')
            ->where('id', '!=', $coa->id)
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get();

        $hasChildren = $coa->children()->exists();
        $saldoAwal   = $this->resolveSaldoAwalContext($coa);

        return view('coa.edit', compact('coa', 'kodeSuffix', 'parents', 'hasChildren', 'saldoAwal'));
    }

    public function update(Request $request, Coa $coa)
    {
        $this->authorizeManager();

        $validated = $this->validateForm($request, ['is_aktif' => 'required|in:0,1']);
        $parent    = $this->resolveParent($validated['parent_id'] ?? null, $coa);
        $kategori  = $parent ? $parent->kategori : $validated['kategori'];
        $kodeAkun  = $this->buildKodeAkun($kategori, $validated['kode_suffix'], $parent);

        if (Coa::where('kode_akun', $kodeAkun)->where('id', '!=', $coa->id)->exists()) {
            return back()
                ->withErrors(['kode_suffix' => "Kode akun {$kodeAkun} sudah digunakan."])
                ->withInput();
        }

        $saldoAwal = $this->resolveSaldoAwalContext($coa);
        $tangki    = $saldoAwal['tangki'];

        // Volume+Harga (akun Persediaan BBM) atau nominal biasa (akun
        // neraca lainnya) - field yang divalidasi tergantung jenis akun,
        // sama seperti pembedaan yang ditampilkan di view.
        $volumeAwal = null;
        $hargaAwal  = null;

        if ($tangki) {
            $inputSaldo = $request->validate([
                'volume_awal' => 'nullable|integer|min:0',
                'harga_awal'  => 'nullable|integer|min:0',
            ]);
            $volumeAwal = (int) ($inputSaldo['volume_awal'] ?? 0);
            $hargaAwal  = (int) ($inputSaldo['harga_awal'] ?? 0);
            $saldoAwalDiisi   = $volumeAwal > 0 && $hargaAwal > 0;
            $saldoAwalNominal = $saldoAwalDiisi ? $volumeAwal * $hargaAwal : 0;
        } elseif (in_array($kategori, ['aset', 'kewajiban', 'modal'], true)) {
            $inputSaldo = $request->validate([
                'saldo_awal' => 'nullable|numeric|min:0',
            ]);
            $saldoAwalNominal = (float) ($inputSaldo['saldo_awal'] ?? 0);
            $saldoAwalDiisi   = $saldoAwalNominal > 0;
        } else {
            $saldoAwalDiisi   = false;
            $saldoAwalNominal = 0;
        }

        // Proteksi dobel input: akun ini sudah terkonfirmasi punya saldo awal
        // (dari sumber lama ATAU baru) tapi field saldo awal tetap diisi -
        // field seharusnya sudah read-only di form, jadi ini jaga-jaga kalau
        // request dikirim manual di luar form.
        if ($saldoAwalDiisi && $saldoAwal['sudah_ada']) {
            return back()
                ->withErrors(['saldo_awal' => "Akun {$coa->kode_akun} {$coa->nama_akun} sudah punya saldo awal (diisi pada {$saldoAwal['tanggal']}), tidak bisa diisi ulang."])
                ->withInput();
        }

        DB::transaction(function () use ($coa, $validated, $parent, $kategori, $kodeAkun, $saldoAwalDiisi, $saldoAwalNominal, $tangki, $volumeAwal, $hargaAwal) {
            $coa->update([
                'kode_akun'     => $kodeAkun,
                'parent_id'     => $parent?->id,
                'nama_akun'     => $validated['nama_akun'],
                'kategori'      => $kategori,
                'posisi_normal' => self::KATEGORI_INFO[$kategori]['posisi'],
                'deskripsi'     => $validated['deskripsi'] ?? null,
                'is_aktif'      => $validated['is_aktif'],
                'saldo_awal'    => $saldoAwalDiisi ? $saldoAwalNominal : $coa->saldo_awal,
            ]);

            if ($saldoAwalDiisi) {
                JurnalService::dariSaldoAwalAkun($coa);

                if ($tangki) {
                    $tangki->update([
                        'stok_liter'        => $volumeAwal,
                        'harga_pokok_rata2' => $hargaAwal,
                    ]);
                }
            }
        });

        return redirect()->route('coa.index')
            ->with('success', 'Akun berhasil diupdate.');
    }

    public function destroy(Coa $coa)
    {
        $this->authorizeManager();

        // Akun (atau salah satu anaknya) yang sudah punya riwayat jurnal tidak
        // boleh dihapus - FK jurnal_detail.coa_id di-restrict, jadi delete()
        // akan gagal dengan raw SQL error kalau ini tidak dicek dulu.
        $akunIds = $coa->children()->pluck('id')->push($coa->id);

        if (JurnalDetail::whereIn('coa_id', $akunIds)->exists()) {
            return redirect()->route('coa.index')->with('error',
                "Akun {$coa->kode_akun} {$coa->nama_akun} tidak bisa dihapus karena sudah memiliki riwayat transaksi. ".
                'Nonaktifkan akun ini saja jika tidak ingin dipakai lagi.'
            );
        }

        $coa->delete();
        return redirect()->route('coa.index')
            ->with('success', 'Akun berhasil dihapus.');
    }

    public function toggleAktif(Coa $coa)
    {
        $this->authorizeManager();

        $coa->update(['is_aktif' => ! $coa->is_aktif]);

        $status = $coa->is_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('coa.index')
            ->with('success', "Akun {$coa->kode_akun} {$coa->nama_akun} berhasil {$status}.");
    }

    private function validateForm(Request $request, array $extraRules = []): array
    {
        return $request->validate(array_merge([
            'kategori'    => 'required_without:parent_id|nullable|in:aset,kewajiban,modal,pendapatan,beban',
            'parent_id'   => 'nullable|exists:coa,id',
            'kode_suffix' => ['required', 'string', 'max:7', 'regex:/^[0-9]+$/'],
            'nama_akun'   => 'required|string|max:100',
            'deskripsi'   => 'nullable|string|max:255',
        ], $extraRules), [
            'kode_suffix.regex'          => 'Kode akun hanya boleh berisi angka.',
            'kategori.required_without' => 'Kategori wajib diisi jika tidak memilih akun parent.',
        ]);
    }

    // Validasi aturan hierarki: parent harus akun top-level (bukan child),
    // tidak boleh jadi parent untuk dirinya sendiri, dan akun yang sudah
    // punya anak tidak boleh diubah jadi child akun lain.
    private function resolveParent(?int $parentId, ?Coa $editing = null): ?Coa
    {
        if (!$parentId) {
            return null;
        }

        if ($editing && $editing->id === $parentId) {
            throw ValidationException::withMessages([
                'parent_id' => 'Akun tidak bisa menjadi parent untuk dirinya sendiri.',
            ]);
        }

        if ($editing && $editing->children()->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Akun ini memiliki akun anak, tidak bisa dijadikan child akun lain.',
            ]);
        }

        $parent = Coa::findOrFail($parentId);

        if ($parent->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Akun parent tidak boleh merupakan child akun lain.',
            ]);
        }

        return $parent;
    }

    private function buildKodeAkun(string $kategori, string $kodeSuffix, ?Coa $parent = null): string
    {
        if ($parent) {
            return $parent->kode_akun . '-' . $kodeSuffix;
        }

        return self::KATEGORI_INFO[$kategori]['prefix'] . $kodeSuffix;
    }

    // Status saldo awal akun ini: sudah pernah diisi atau belum (dari sumber
    // lama 'saldo_awal' ATAU baru 'saldo_awal_akun' - lihat catatan investigasi
    // di JurnalService), plus tangki terkait kalau akun ini adalah salah satu
    // akun Persediaan BBM (1104-1/2/3, di-resolve via master_bbm.coa_persediaan_id
    // supaya tetap benar walau kode_akun berubah).
    //
    // 'nominal' diambil dari jurnal_detail.jumlah (bukan coa.saldo_awal) karena
    // mekanisme lama TIDAK PERNAH mengisi kolom coa.saldo_awal - jumlah di
    // jurnal adalah satu-satunya sumber yang valid untuk kedua mekanisme.
    // Untuk akun Persediaan BBM, volume & harga historis presisi saat saldo
    // awal pertama diinput tidak tersimpan terpisah di manapun (mekanisme lama
    // cuma menyimpan nominal gabungan) - yang ditampilkan sebagai read-only
    // adalah kondisi tangki TERKINI (stok_liter/harga_pokok_rata2), yang bisa
    // saja sudah bergeser dari angka input awal akibat pembelian/penjualan
    // berikutnya.
    private function resolveSaldoAwalContext(Coa $coa): array
    {
        $detail = JurnalDetail::where('coa_id', $coa->id)
            ->whereHas('jurnal', fn ($q) => $q->whereIn('sumber', self::SUMBER_SALDO_AWAL))
            ->with('jurnal')
            ->first();

        $masterBbm = MasterBbm::where('coa_persediaan_id', $coa->id)->first();
        $tangki    = $masterBbm ? TangkiBbm::where('master_bbm_id', $masterBbm->id)->first() : null;

        return [
            'sudah_ada' => $detail !== null,
            'tanggal'   => $detail?->jurnal?->tanggal?->format('d/m/Y'),
            'nominal'   => $detail?->jumlah,
            'tangki'    => $tangki,
        ];
    }

    private function authorizeManager()
    {
        if (auth()->user()->role !== 'manager') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }
}
