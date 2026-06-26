<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use Illuminate\Http\Request;

class CoaController extends Controller
{
    public function index()
    {
        $data = Coa::orderBy('kode_akun')->get()->groupBy('kategori');
        return view('coa.index', compact('data'));
    }

    public function create()
    {
        $this->authorizeIT();
        return view('coa.create');
    }

    public function store(Request $request)
    {
        $this->authorizeIT();

        $request->validate([
            'kode_akun'     => 'required|string|max:10|unique:coa,kode_akun',
            'nama_akun'     => 'required|string|max:100',
            'kategori'      => 'required|in:aset,kewajiban,modal,pendapatan,beban',
            'posisi_normal' => 'required|in:debit,kredit',
            'deskripsi'     => 'nullable|string|max:255',
        ]);

        Coa::create($request->only([
            'kode_akun', 'nama_akun', 'kategori', 'posisi_normal', 'deskripsi'
        ]) + ['is_aktif' => 1]);

        return redirect()->route('coa.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(Coa $coa)
    {
        $this->authorizeIT();
        return view('coa.edit', compact('coa'));
    }

    public function update(Request $request, Coa $coa)
    {
        $this->authorizeIT();

        $request->validate([
            'kode_akun'     => 'required|string|max:10|unique:coa,kode_akun,' . $coa->id,
            'nama_akun'     => 'required|string|max:100',
            'kategori'      => 'required|in:aset,kewajiban,modal,pendapatan,beban',
            'posisi_normal' => 'required|in:debit,kredit',
            'deskripsi'     => 'nullable|string|max:255',
            'is_aktif'      => 'required|in:0,1',
        ]);

        $coa->update($request->only([
            'kode_akun', 'nama_akun', 'kategori', 'posisi_normal', 'deskripsi', 'is_aktif'
        ]));

        return redirect()->route('coa.index')
            ->with('success', 'Akun berhasil diupdate.');
    }

    public function destroy(Coa $coa)
    {
        $this->authorizeIT();
        $coa->delete();
        return redirect()->route('coa.index')
            ->with('success', 'Akun berhasil dihapus.');
    }

    private function authorizeIT()
    {
        if (auth()->user()->role !== 'it') {
            abort(403, 'Hanya IT yang dapat melakukan aksi ini.');
        }
    }
}