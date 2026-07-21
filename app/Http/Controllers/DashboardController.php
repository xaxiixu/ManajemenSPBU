<?php

namespace App\Http\Controllers;

use App\Models\PenjualanBbm;
use App\Models\Pengeluaran;
use App\Models\Absensis;
use App\Models\JurnalDetail;
use App\Models\Coa;
use App\Models\ShiftMaster;
use App\Models\PayrollSetting;
use App\Services\PayrollService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(PayrollService $payroll)
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

        // ── Reminder payroll (hanya manager yang bisa proses) ──
        $reminderPayroll = auth()->user()->role === 'manager'
            ? $this->reminderPayroll($payroll)
            : null;

        return view('dashboard', compact(
            'penjualanHariIni', 'pengeluaranHariIni', 'labaHariIni', 'petugasHadir', 'tanggalOperasional',
            'penjualanBulanIni', 'pengeluaranBulanIni', 'labaBulanIni',
            'grafikLabels', 'grafikData', 'perJenis', 'transaksiTerbaru',
            'reminderPayroll'
        ));
    }

    /**
     * Periode payroll yang perlu segera diproses: periode berjalan yang sudah
     * masuk H-3 sebelum tanggal gajian (atau periode lampau yang sudah lewat
     * tanggal gajian) DAN belum ada run berstatus 'dikirim'. Mengembalikan
     * periode paling mendesak, atau null kalau tidak ada.
     */
    private function reminderPayroll(PayrollService $payroll): ?array
    {
        $setting  = PayrollSetting::get();
        $kandidat = $payroll->daftarKandidatPeriode(3, $setting); // berjalan + 2 ke belakang
        $today    = Carbon::today();

        foreach ($kandidat as $k) {
            $sudahDikirim = $k['run'] && $k['run']->status === 'dikirim';
            if ($sudahDikirim) {
                continue;
            }

            // Masuk jendela H-3 sebelum tanggal gajian (atau sudah terlewat).
            $mulaiIngatkan = $k['selesai']->copy()->subDays(3);
            if ($today->greaterThanOrEqualTo($mulaiIngatkan)) {
                return [
                    'mulai'   => $k['mulai'],
                    'selesai' => $k['selesai'],
                    'overdue' => $today->greaterThan($k['selesai']),
                ];
            }
        }

        return null;
    }
}