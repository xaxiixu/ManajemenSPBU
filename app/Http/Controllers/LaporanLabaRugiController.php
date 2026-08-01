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

        // ── Pendapatan bulan ini ───────────────────────
        $pendapatan = Coa::where('kategori', 'pendapatan')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get()
            ->map(function($coa) use ($tahun, $bln) {
                $coa->total = JurnalDetail::where('coa_id', $coa->id)
                    ->where('posisi', 'kredit')
                    ->whereHas('jurnal', fn($q) => $q
                        ->whereYear('tanggal', $tahun)
                        ->whereMonth('tanggal', $bln))
                    ->sum('jumlah');
                return $coa;
            });

        // ── HPP bulan ini (akun 5104 saja) ──────────────
        $hpp = Coa::where('kategori', 'beban')
            ->where('kode_akun', '5104')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get()
            ->map(function($coa) use ($tahun, $bln) {
                $coa->total = JurnalDetail::where('coa_id', $coa->id)
                    ->where('posisi', 'debit')
                    ->whereHas('jurnal', fn($q) => $q
                        ->whereYear('tanggal', $tahun)
                        ->whereMonth('tanggal', $bln))
                    ->sum('jumlah');
                return $coa;
            });

        // ── Beban Operasional bulan ini (semua akun beban KECUALI 5104) ─
        $bebanOperasional = Coa::where('kategori', 'beban')
            ->where('kode_akun', '!=', '5104')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get()
            ->map(function($coa) use ($tahun, $bln) {
                $coa->total = JurnalDetail::where('coa_id', $coa->id)
                    ->where('posisi', 'debit')
                    ->whereHas('jurnal', fn($q) => $q
                        ->whereYear('tanggal', $tahun)
                        ->whereMonth('tanggal', $bln))
                    ->sum('jumlah');
                return $coa;
            });

        $totalPendapatan       = $pendapatan->sum('total');
        $totalHpp              = $hpp->sum('total');
        $totalBebanOperasional = $bebanOperasional->sum('total');

        // Laba Kotor = Pendapatan - HPP, lalu Laba Bersih = Laba Kotor -
        // Beban Operasional. Dipecah jadi 2 langkah supaya strukturnya
        // proper income statement (ada baris Laba Kotor di antaranya).
        $labaKotor  = $totalPendapatan - $totalHpp;
        $labaBersih = $labaKotor - $totalBebanOperasional;

        return view('laporan-laba-rugi.index', compact(
            'pendapatan', 'hpp', 'bebanOperasional',
            'totalPendapatan', 'totalHpp', 'totalBebanOperasional',
            'labaKotor', 'labaBersih',
            'bulan'
        ));
    }
}