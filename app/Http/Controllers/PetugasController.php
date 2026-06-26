<?php

namespace App\Http\Controllers;

use App\Models\Petugas;
use Illuminate\Http\Request;

class PetugasController extends Controller
{
    public function index()
    {
        $data = Petugas::orderBy('nama')->get();
        return view('petugas.index', compact('data'));
    }

    public function create()
    {
        return view('petugas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'          => 'required|string|max:100',
            'nik'           => 'nullable|string|max:20|unique:petugas,nik',
            'jabatan'       => 'required|in:operator,supervisor,kasir,teknisi,lainnya',
            'shift_default' => 'nullable|in:Pagi,Siang,Malam',
            'no_hp'         => 'nullable|string|max:20',
        ]);

        Petugas::create($request->only([
            'nama', 'nik', 'jabatan', 'shift_default', 'no_hp'
        ]) + ['is_aktif' => 1]);

        return redirect()->route('petugas.index')
            ->with('success', 'Data petugas berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $petugas = Petugas::findOrFail($id);
        return view('petugas.edit', compact('petugas'));
    }

    public function update(Request $request, $id)
    {
        $petugas = Petugas::findOrFail($id);

        $request->validate([
            'nama'          => 'required|string|max:100',
            'nik'           => 'nullable|string|max:20|unique:petugas,nik,' . $id,
            'jabatan'       => 'required|in:operator,supervisor,kasir,teknisi,lainnya',
            'shift_default' => 'nullable|in:Pagi,Siang,Malam',
            'no_hp'         => 'nullable|string|max:20',
            'is_aktif'      => 'required|in:0,1',
        ]);

        $petugas->update($request->only([
            'nama', 'nik', 'jabatan', 'shift_default', 'no_hp', 'is_aktif'
        ]));

        return redirect()->route('petugas.index')
            ->with('success', 'Data petugas berhasil diupdate.');
    }

    public function destroy($id)
    {
        $petugas = Petugas::findOrFail($id);
        $petugas->delete();

        return redirect()->route('petugas.index')
            ->with('success', 'Data petugas berhasil dihapus.');
    }
}