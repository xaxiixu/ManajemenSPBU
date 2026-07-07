<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use App\Models\MasterBbm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterBbmController extends Controller
{
    public function index()
    {
        $data = MasterBbm::with('coa')->orderBy('jenis_bbm')->get();

        return view('master-bbm.index', compact('data'));
    }

    public function create()
    {
        $coaPendapatan = $this->coaPendapatanOptions();

        return view('master-bbm.create', compact('coaPendapatan'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateForm($request);

        MasterBbm::create([
            'jenis_bbm'         => $validated['jenis_bbm'],
            'ron'               => $validated['ron'] ?? null,
            'harga_per_liter'   => $validated['harga_per_liter'],
            'coa_pendapatan_id' => $validated['coa_pendapatan_id'],
            'is_aktif'          => 1,
        ]);

        return redirect()->route('master-bbm.index')
            ->with('success', 'Jenis BBM berhasil ditambahkan.');
    }

    public function edit(MasterBbm $masterBbm)
    {
        $coaPendapatan = $this->coaPendapatanOptions();

        return view('master-bbm.edit', compact('masterBbm', 'coaPendapatan'));
    }

    public function update(Request $request, MasterBbm $masterBbm)
    {
        $validated = $this->validateForm($request, $masterBbm->id, ['is_aktif' => 'required|in:0,1']);

        $masterBbm->update([
            'jenis_bbm'         => $validated['jenis_bbm'],
            'ron'               => $validated['ron'] ?? null,
            'harga_per_liter'   => $validated['harga_per_liter'],
            'coa_pendapatan_id' => $validated['coa_pendapatan_id'],
            'is_aktif'          => $validated['is_aktif'],
        ]);

        return redirect()->route('master-bbm.index')
            ->with('success', 'Jenis BBM berhasil diupdate.');
    }

    public function destroy(MasterBbm $masterBbm)
    {
        $masterBbm->delete();

        return redirect()->route('master-bbm.index')
            ->with('success', 'Jenis BBM berhasil dihapus.');
    }

    private function validateForm(Request $request, ?int $ignoreId = null, array $extraRules = []): array
    {
        return $request->validate(array_merge([
            'jenis_bbm'         => [
                'required', 'string', 'max:50',
                Rule::unique('master_bbm', 'jenis_bbm')->ignore($ignoreId),
            ],
            'ron'               => 'nullable|string|max:10',
            'harga_per_liter'   => 'required|integer|min:1',
            'coa_pendapatan_id' => 'required|exists:coa,id',
        ], $extraRules));
    }

    // Akun pendapatan (kategori pendapatan, aktif) sebagai pilihan COA di form
    private function coaPendapatanOptions()
    {
        return Coa::where('kategori', 'pendapatan')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get();
    }
}
