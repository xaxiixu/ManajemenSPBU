<?php

namespace App\Http\Controllers;

use App\Models\Absensis;
use App\Models\ShiftMaster;

class PresensiController extends Controller
{
    // Halaman self-service: status hari ini + riwayat milik sendiri
    public function index()
    {
        $userId = auth()->id();
        $shiftDefault = auth()->user()->shift_default;

        $sesiTerbuka = Absensis::where('user_id', $userId)
            ->where('status_hadir', 'hadir')
            ->whereNull('jam_keluar')
            ->latest('tanggal')
            ->first();

        $absenHariIni = Absensis::where('user_id', $userId)
            ->whereDate('tanggal', today())
            ->first();

        $riwayat = Absensis::where('user_id', $userId)
            ->latest('tanggal')
            ->take(30)
            ->get();

        $shiftMaster = $shiftDefault
            ? ShiftMaster::where('shift', $shiftDefault)->first()
            : null;

        return view('presensi.index', compact('sesiTerbuka', 'absenHariIni', 'riwayat', 'shiftMaster'));
    }

    public function absenMasuk()
    {
        $userId = auth()->id();
        $shift = auth()->user()->shift_default;

        if (!$shift) {
            return back()->withErrors(['shift' => 'Shift Anda belum diatur. Hubungi pengawas untuk mengatur jadwal shift Anda.']);
        }

        $sesiTerbuka = Absensis::where('user_id', $userId)
            ->where('status_hadir', 'hadir')
            ->whereNull('jam_keluar')
            ->latest('tanggal')
            ->first();

        if ($sesiTerbuka) {
            return back()->withErrors(['shift' =>
                'Anda masih punya sesi absensi yang belum di-absen keluar pada tanggal '
                . $sesiTerbuka->tanggal->format('d/m/Y')
                . '. Hubungi pengawas untuk menutup sesi tersebut.'
            ]);
        }

        if (Absensis::where('user_id', $userId)->whereDate('tanggal', today())->exists()) {
            return back()->withErrors(['shift' => 'Anda sudah melakukan presensi hari ini.']);
        }

        $jamMasuk = now();
        $menitTelat = 0;
        $shiftMaster = ShiftMaster::where('shift', $shift)->first();

        if ($shiftMaster) {
            $menitTelat = Absensis::hitungMenitTelat($shiftMaster, $jamMasuk->copy()->startOfDay(), $jamMasuk->format('H:i:s'));
        }

        Absensis::create([
            'user_id'      => $userId,
            'tanggal'      => today(),
            'shift'        => $shift,
            'status_hadir' => 'hadir',
            'jam_masuk'    => $jamMasuk->format('H:i:s'),
            'menit_telat'  => $menitTelat,
            'keterangan'   => $menitTelat > 0 ? "Telat {$menitTelat} menit" : null,
            'dicatat_oleh' => $userId,
        ]);

        return redirect()->route('presensi.index')->with('success', 'Absen masuk berhasil dicatat.');
    }

    public function absenKeluar()
    {
        $userId = auth()->id();

        $sesi = Absensis::where('user_id', $userId)
            ->where('status_hadir', 'hadir')
            ->whereNull('jam_keluar')
            ->latest('tanggal')
            ->first();

        if (!$sesi) {
            return back()->withErrors(['presensi' => 'Tidak ada sesi absen masuk yang terbuka.']);
        }

        $sesi->update(['jam_keluar' => now()->format('H:i:s')]);

        return redirect()->route('presensi.index')->with('success', 'Absen keluar berhasil dicatat.');
    }
}
