<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
        $totalBeban            = $totalHpp + $totalBebanOperasional;

        // Laba Kotor = Pendapatan - HPP, lalu Laba Bersih = Laba Kotor -
        // Beban Operasional. Secara nominal identik dengan totalPendapatan -
        // totalBeban lama (totalBeban = totalHpp + totalBebanOperasional),
        // cuma dipecah jadi 2 langkah supaya strukturnya proper income
        // statement (ada baris Laba Kotor di antaranya).
        $labaKotor  = $totalPendapatan - $totalHpp;
        $labaBersih = $labaKotor - $totalBebanOperasional;

        // ── Tren 6 bulan terakhir ──────────────────────
        $trenLabels     = [];
        $trenPendapatan = [];
        $trenBeban      = [];
        $trenLaba       = [];

        for ($i = 5; $i >= 0; $i--) {
            $tgl   = Carbon::parse($bulan . '-01')->subMonths($i);
            $y     = $tgl->year;
            $m     = $tgl->month;

            $trenLabels[] = $tgl->translatedFormat('M Y');

            $p = JurnalDetail::where('posisi', 'kredit')
                ->whereHas('jurnal', fn($q) => $q->whereYear('tanggal', $y)->whereMonth('tanggal', $m))
                ->whereHas('coa', fn($q) => $q->where('kategori', 'pendapatan'))
                ->sum('jumlah');

            $b = JurnalDetail::where('posisi', 'debit')
                ->whereHas('jurnal', fn($q) => $q->whereYear('tanggal', $y)->whereMonth('tanggal', $m))
                ->whereHas('coa', fn($q) => $q->where('kategori', 'beban'))
                ->sum('jumlah');

            $trenPendapatan[] = $p;
            $trenBeban[]      = $b;
            $trenLaba[]       = $p - $b;
        }

        $tren = [
            'labels'     => $trenLabels,
            'pendapatan' => $trenPendapatan,
            'beban'      => $trenBeban,
            'laba'       => $trenLaba,
        ];

        // ── Pie chart data ─────────────────────────────
        $pieData = [
            'pendapatan' => [
                'labels' => $pendapatan->where('total', '>', 0)->pluck('nama_akun')->values()->toArray(),
                'values' => $pendapatan->where('total', '>', 0)->pluck('total')->values()->toArray(),
            ],
            'beban' => [
                'labels' => $bebanOperasional->where('total', '>', 0)->pluck('nama_akun')->values()->toArray(),
                'values' => $bebanOperasional->where('total', '>', 0)->pluck('total')->values()->toArray(),
            ],
        ];

        return view('laporan-laba-rugi.index', compact(
            'pendapatan', 'hpp', 'bebanOperasional',
            'totalPendapatan', 'totalHpp', 'totalBebanOperasional', 'totalBeban',
            'labaKotor', 'labaBersih',
            'bulan', 'tren', 'pieData'
        ));
    }
}