<?php

namespace App\Http\Controllers;

use App\Models\Absensis;
use App\Models\Petugas;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    public function index(Request $request)
    {
        $bulan  = $request->bulan;
        $shift  = $request->shift;

        if ($bulan) {
            // Filter pakai bulan
            $dari   = \Carbon\Carbon::parse($bulan . '-01')->startOfMonth()->toDateString();
            $sampai = \Carbon\Carbon::parse($bulan . '-01')->endOfMonth()->toDateString();
        } else {
            // Filter pakai range tanggal
            $dari   = $request->dari   ?? today()->toDateString();
            $sampai = $request->sampai ?? today()->toDateString();
        }

        $query = Absensis::with('petugas')
            ->whereBetween('tanggal', [$dari, $sampai]);

        if ($shift) {
            $query->where('shift', $shift);
        }

        $data = $query->orderBy('tanggal')->orderBy('shift')->get();

        $ringkasan = [
            'hadir'       => $data->where('status_hadir', 'hadir')->count(),
            'sakit'       => $data->where('status_hadir', 'sakit')->count(),
            'izin'        => $data->where('status_hadir', 'izin')->count(),
            'tidak_hadir' => $data->where('status_hadir', 'tidak_hadir')->count(),
        ];

        return view('presensi.index', compact('data', 'dari', 'sampai', 'bulan', 'shift', 'ringkasan'));
    }

    public function create()
    {
        $petugas = Petugas::where('is_aktif', 1)->orderBy('nama')->get();
        return view('presensi.create', compact('petugas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'petugas_id'   => 'required|exists:petugas,id',
            'tanggal'      => 'required|date',
            'shift'        => 'required|in:Pagi,Siang,Malam',
            'status_hadir' => 'required|in:hadir,sakit,izin,tidak_hadir',
            'jam_masuk'    => 'nullable|date_format:H:i',
            'jam_keluar'   => 'nullable|date_format:H:i',
            'keterangan'   => 'nullable|string|max:255',
        ]);

        $exists = Absensis::where('petugas_id', $request->petugas_id)
            ->whereDate('tanggal', $request->tanggal)
            ->where('shift', $request->shift)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['petugas_id' => 'Petugas ini sudah diinput untuk shift dan tanggal yang sama.'])
                ->withInput();
        }

        Absensis::create([
            'petugas_id'   => $request->petugas_id,
            'tanggal'      => $request->tanggal,
            'shift'        => $request->shift,
            'status_hadir' => $request->status_hadir,
            'jam_masuk'    => $request->jam_masuk,
            'jam_keluar'   => $request->jam_keluar,
            'keterangan'   => $request->keterangan,
            'dicatat_oleh' => auth()->id(),
        ]);

        return redirect()->route('presensi.index')
            ->with('success', 'Data presensi berhasil disimpan.');
    }

    public function edit(Absensis $presensi)
    {
        $petugas = Petugas::where('is_aktif', 1)->orderBy('nama')->get();
        return view('presensi.edit', compact('presensi', 'petugas'));
    }

    public function update(Request $request, Absensis $presensi)
    {
        $request->validate([
            'status_hadir' => 'required|in:hadir,sakit,izin,tidak_hadir',
            'jam_masuk'    => 'nullable|date_format:H:i',
            'jam_keluar'   => 'nullable|date_format:H:i',
            'keterangan'   => 'nullable|string|max:255',
        ]);

        $presensi->update([
            'status_hadir' => $request->status_hadir,
            'jam_masuk'    => $request->jam_masuk,
            'jam_keluar'   => $request->jam_keluar,
            'keterangan'   => $request->keterangan,
        ]);

        return redirect()->route('presensi.index')
            ->with('success', 'Data presensi berhasil diupdate.');
    }

    public function destroy(Absensis $presensi)
    {
        $presensi->delete();
        return redirect()->route('presensi.index')
            ->with('success', 'Data presensi berhasil dihapus.');
    }
}