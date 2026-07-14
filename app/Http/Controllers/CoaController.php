<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use Illuminate\Http\Request;
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

        $parents = Coa::whereNull('parent_id')->orderBy('kode_akun')->get();

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

        $validated = $this->validateForm($request);
        $parent    = $this->resolveParent($validated['parent_id'] ?? null);
        $kategori  = $parent ? $parent->kategori : $validated['kategori'];
        $kodeAkun  = $this->buildKodeAkun($kategori, $validated['kode_suffix'], $parent);

        if (Coa::where('kode_akun', $kodeAkun)->exists()) {
            return back()
                ->withErrors(['kode_suffix' => "Kode akun {$kodeAkun} sudah digunakan."])
                ->withInput();
        }

        Coa::create([
            'kode_akun'     => $kodeAkun,
            'parent_id'     => $parent?->id,
            'nama_akun'     => $validated['nama_akun'],
            'kategori'      => $kategori,
            'posisi_normal' => self::KATEGORI_INFO[$kategori]['posisi'],
            'deskripsi'     => $validated['deskripsi'] ?? null,
            'is_aktif'      => 1,
        ]);

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
            ->orderBy('kode_akun')
            ->get();

        $hasChildren = $coa->children()->exists();

        return view('coa.edit', compact('coa', 'kodeSuffix', 'parents', 'hasChildren'));
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

        $coa->update([
            'kode_akun'     => $kodeAkun,
            'parent_id'     => $parent?->id,
            'nama_akun'     => $validated['nama_akun'],
            'kategori'      => $kategori,
            'posisi_normal' => self::KATEGORI_INFO[$kategori]['posisi'],
            'deskripsi'     => $validated['deskripsi'] ?? null,
            'is_aktif'      => $validated['is_aktif'],
        ]);

        return redirect()->route('coa.index')
            ->with('success', 'Akun berhasil diupdate.');
    }

    public function destroy(Coa $coa)
    {
        $this->authorizeManager();
        $coa->delete();
        return redirect()->route('coa.index')
            ->with('success', 'Akun berhasil dihapus.');
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

    private function authorizeManager()
    {
        if (auth()->user()->role !== 'manager') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }
}
