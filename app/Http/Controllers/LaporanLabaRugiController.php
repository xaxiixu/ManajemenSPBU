<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;

class LaporanLabaRugiController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->bulan ?? now()->format('Y-m');
        $tahun = substr($bulan, 0, 4);
        $bln   = substr($bulan, 5, 2);

        // Ambil semua akun pendapatan beserta totalnya
        $pendapatan = Coa::where('kategori', 'pendapatan')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get()
            ->map(function($coa) use ($tahun, $bln) {
                $total = JurnalDetail::where('coa_id', $coa->id)
                    ->where('posisi', 'kredit')
                    ->whereHas('jurnal', function($q) use ($tahun, $bln) {
                        $q->whereYear('tanggal', $tahun)
                          ->whereMonth('tanggal', $bln);
                    })
                    ->sum('jumlah');
                $coa->total = $total;
                return $coa;
            });

        // Ambil semua akun beban beserta totalnya
        $beban = Coa::where('kategori', 'beban')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get()
            ->map(function($coa) use ($tahun, $bln) {
                $total = JurnalDetail::where('coa_id', $coa->id)
                    ->where('posisi', 'debit')
                    ->whereHas('jurnal', function($q) use ($tahun, $bln) {
                        $q->whereYear('tanggal', $tahun)
                          ->whereMonth('tanggal', $bln);
                    })
                    ->sum('jumlah');
                $coa->total = $total;
                return $coa;
            });

        $totalPendapatan = $pendapatan->sum('total');
        $totalBeban      = $beban->sum('total');
        $labaRugi        = $totalPendapatan - $totalBeban;

        return view('laporan-laba-rugi.index', compact(
            'pendapatan', 'beban',
            'totalPendapatan', 'totalBeban',
            'labaRugi', 'bulan'
        ));
    }
}