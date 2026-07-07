<?php

namespace App\Http\Controllers;

use App\Models\Absensis;
use App\Models\ShiftMaster;
use App\Models\User;
use Illuminate\Http\Request;

class PetugasController extends Controller
{
    public function index()
    {
        $petugas = User::where('role', 'petugas')->orderBy('name')->get();

        // Tanggal operasional (bukan kalender polos) - supaya konsisten dengan
        // Dashboard: kalau shift Malam kemarin masih berlangsung lewat tengah
        // malam, "hadir hari ini" tetap merujuk ke tanggal shift itu.
        $tanggalOperasional = ShiftMaster::tanggalOperasionalSaatIni();

        $hadirHariIni = Absensis::with('user')
            ->whereDate('tanggal', $tanggalOperasional['tanggal'])
            ->where('status_hadir', 'hadir')
            ->get()
            ->groupBy('shift');

        return view('petugas.index', compact('petugas', 'hadirHariIni', 'tanggalOperasional'));
    }

    public function show($id)
    {
        $petugas = User::where('role', 'petugas')->findOrFail($id);

        $riwayat = Absensis::where('user_id', $id)
            ->latest('tanggal')
            ->take(30)
            ->get();

        return view('petugas.show', compact('petugas', 'riwayat'));
    }

    // Pengawas/Manager/IT input status sakit/izin/tidak_hadir untuk petugas
    // (status 'hadir' hanya lewat self-service presensi milik petugas sendiri)
    public function markAbsensi(Request $request, $id)
    {
        $petugas = User::where('role', 'petugas')->findOrFail($id);

        $validated = $request->validate([
            'tanggal'      => 'required|date',
            'status_hadir' => 'required|in:sakit,izin,tidak_hadir',
            'keterangan'   => 'nullable|string|max:255',
        ]);

        if (Absensis::where('user_id', $id)->whereDate('tanggal', $validated['tanggal'])->exists()) {
            return back()
                ->withErrors(['tanggal' => 'Sudah ada data presensi untuk petugas ini pada tanggal tersebut.'])
                ->withInput();
        }

        Absensis::create([
            'user_id'      => $petugas->id,
            'tanggal'      => $validated['tanggal'],
            'shift'        => $petugas->shift_default ?? 'Pagi',
            'status_hadir' => $validated['status_hadir'],
            'keterangan'   => $validated['keterangan'],
            'dicatat_oleh' => auth()->id(),
        ]);

        return redirect()->route('petugas.index')
            ->with('success', 'Status presensi petugas berhasil dicatat.');
    }

    // Pengawas/Manager/IT: koreksi record absensi (termasuk force-close sesi yang nyangkut)
    public function updateAbsensi(Request $request, Absensis $absensi)
    {
        $validated = $request->validate([
            'status_hadir'         => 'required|in:hadir,sakit,izin,tidak_hadir',
            'jam_masuk'            => 'nullable|date_format:H:i',
            'jam_keluar'           => 'nullable|date_format:H:i',
            'menit_pulang_cepat'   => 'nullable|integer|min:0',
            'flag_kelebihan_waktu' => 'nullable|boolean',
            'keterangan'           => 'nullable|string|max:255',
        ]);

        $jamMasuk = $validated['jam_masuk'] ? $validated['jam_masuk'] . ':00' : null;
        $jamKeluar = $validated['jam_keluar'] ? $validated['jam_keluar'] . ':00' : null;

        $menitTelat = 0;
        if ($jamMasuk) {
            $shiftMaster = ShiftMaster::where('shift', $absensi->shift)->first();
            if ($shiftMaster) {
                $menitTelat = Absensis::hitungMenitTelat($shiftMaster, $absensi->tanggal, $jamMasuk);
            }
        }

        // menit_pulang_cepat & flag_kelebihan_waktu dihitung otomatis saat absen
        // keluar (lihat PresensiController::absenKeluar()), tapi pengawas/manager
        // tetap bisa koreksi manual di sini (mis. kalau hasil otomatis meleset
        // karena data lembur/shift menyusul diperbaiki setelah presensi terjadi).
        $absensi->update([
            'status_hadir'         => $validated['status_hadir'],
            'jam_masuk'            => $jamMasuk,
            'jam_keluar'           => $jamKeluar,
            'menit_telat'          => $menitTelat,
            'menit_pulang_cepat'   => $validated['menit_pulang_cepat'] ?? 0,
            'flag_kelebihan_waktu' => $request->boolean('flag_kelebihan_waktu'),
            'keterangan'           => $validated['keterangan'] ?? null,
        ]);

        return redirect()->route('petugas.show', $absensi->user_id)
            ->with('success', 'Data presensi berhasil diperbarui.');
    }

    // Pengawas/Manager/IT: hapus record absensi yang salah
    public function destroyAbsensi(Absensis $absensi)
    {
        $userId = $absensi->user_id;

        $absensi->delete();

        return redirect()->route('petugas.show', $userId)
            ->with('success', 'Data presensi berhasil dihapus.');
    }
}
