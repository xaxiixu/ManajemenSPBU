<?php

namespace App\Http\Controllers;

use App\Models\PenjualanBbm;
use App\Models\Pengeluaran;
use App\Models\Absensis;
use App\Models\JurnalDetail;
use App\Models\Coa;
use App\Models\ShiftMaster;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Hari ini (pakai tanggal operasional, bukan kalender polos) ──
        // Penjualan/pengeluaran/petugas hadir dicatat dengan tanggal shift-nya
        // (sama seperti absensi) - kalau shift Malam kemarin masih berlangsung
        // lewat tengah malam, statistik "hari ini" harus tetap merujuk ke
        // tanggal shift itu, bukan tanggal kalender yang sudah berganti.
        $tanggalOperasional = ShiftMaster::tanggalOperasionalSaatIni();

        $penjualanHariIni = PenjualanBbm::whereDate('tanggal', $tanggalOperasional['tanggal'])->sum('total_penjualan');
        $pengeluaranHariIni = Pengeluaran::whereDate('tanggal', $tanggalOperasional['tanggal'])->sum('jumlah');
        $labaHariIni = $penjualanHariIni - $pengeluaranHariIni;
        $petugasHadir = Absensis::whereDate('tanggal', $tanggalOperasional['tanggal'])
            ->where('status_hadir', 'hadir')->count();

        // ── Bulan ini ─────────────────────────────────
        $penjualanBulanIni = PenjualanBbm::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)->sum('total_penjualan');
        $pengeluaranBulanIni = Pengeluaran::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)->sum('jumlah');
        $labaBulanIni = $penjualanBulanIni - $pengeluaranBulanIni;

        // ── Grafik 7 hari terakhir ────────────────────
        $grafik = PenjualanBbm::select(
                DB::raw('DATE(tanggal) as tgl'),
                DB::raw('SUM(total_penjualan) as total')
            )
            ->whereBetween('tanggal', [now()->subDays(6), now()])
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->get()
            ->keyBy('tgl');

        $grafikLabels = [];
        $grafikData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = now()->subDays($i)->format('Y-m-d');
            $grafikLabels[] = now()->subDays($i)->format('d/m');
            $grafikData[]   = $grafik[$tgl]->total ?? 0;
        }

        // ── Penjualan per jenis BBM bulan ini ─────────
        $perJenis = PenjualanBbm::select('jenis_bbm',
                DB::raw('SUM(liter_terjual) as liter'),
                DB::raw('SUM(total_penjualan) as total')
            )
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->groupBy('jenis_bbm')
            ->get();

        // ── Transaksi terbaru ─────────────────────────
        $transaksiTerbaru = PenjualanBbm::with('absensis.user')
            ->latest('tanggal')->take(5)->get();

        return view('dashboard', compact(
            'penjualanHariIni', 'pengeluaranHariIni', 'labaHariIni', 'petugasHadir', 'tanggalOperasional',
            'penjualanBulanIni', 'pengeluaranBulanIni', 'labaBulanIni',
            'grafikLabels', 'grafikData', 'perJenis', 'transaksiTerbaru'
        ));
    }
}