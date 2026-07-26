<?php

namespace App\Http\Controllers;

use App\Models\PenjualanBbm;
use App\Models\Pengeluaran;
use App\Models\Absensis;
use App\Models\JurnalDetail;
use App\Models\Coa;
use App\Models\ShiftMaster;
use App\Models\PayrollSetting;
use App\Models\TangkiBbm;
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
        $labaHariIni = $this->labaAkuntansi(
            fn ($q) => $q->whereDate('tanggal', $tanggalOperasional['tanggal'])
        );
        $petugasHadir = Absensis::whereDate('tanggal', $tanggalOperasional['tanggal'])
            ->where('status_hadir', 'hadir')->count();

        // ── Bulan ini ─────────────────────────────────
        $penjualanBulanIni = PenjualanBbm::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)->sum('total_penjualan');
        $pengeluaranBulanIni = Pengeluaran::whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)->sum('jumlah');
        $labaBulanIni = $this->labaAkuntansi(
            fn ($q) => $q->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)
        );

        // ── Saldo Kas (akun 1101, berjalan sampai hari ini) ─────
        $saldoKas = JurnalDetail::where('posisi', 'debit')
            ->whereHas('coa', fn ($q) => $q->where('kode_akun', '1101'))
            ->sum('jumlah')
            - JurnalDetail::where('posisi', 'kredit')
            ->whereHas('coa', fn ($q) => $q->where('kode_akun', '1101'))
            ->sum('jumlah');

        // ── Nilai Persediaan BBM (snapshot kondisi tangki saat ini) ─
        $nilaiPersediaan = TangkiBbm::where('is_aktif', 1)->get()
            ->sum(fn ($t) => $t->stok_liter * $t->harga_pokok_rata2);

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
            'saldoKas', 'nilaiPersediaan',
            'grafikLabels', 'grafikData', 'perJenis', 'transaksiTerbaru',
            'reminderPayroll'
        ));
    }

    /**
     * Laba akuntansi yang benar untuk rentang tanggal tertentu: Pendapatan
     * (jurnal kredit akun kategori pendapatan) − HPP (jurnal debit akun 5104)
     * − Beban lain (jurnal debit akun kategori beban selain 5104, termasuk
     * beban gaji payroll 5103). Pola query sama seperti
     * LaporanLabaRugiController supaya angkanya selalu match untuk periode
     * yang sama - sengaja TIDAK lagi dihitung dari PenjualanBbm::sum()/
     * Pengeluaran::sum() langsung (itu mengabaikan HPP & beban gaji payroll).
     *
     * @param  \Closure(\Illuminate\Database\Eloquent\Builder): void  $filterJurnal  filter tanggal pada query JurnalUmum
     */
    private function labaAkuntansi(\Closure $filterJurnal): int
    {
        $pendapatan = JurnalDetail::where('posisi', 'kredit')
            ->whereHas('coa', fn ($q) => $q->where('kategori', 'pendapatan'))
            ->whereHas('jurnal', $filterJurnal)
            ->sum('jumlah');

        $hpp = JurnalDetail::where('posisi', 'debit')
            ->whereHas('coa', fn ($q) => $q->where('kode_akun', '5104'))
            ->whereHas('jurnal', $filterJurnal)
            ->sum('jumlah');

        $bebanLain = JurnalDetail::where('posisi', 'debit')
            ->whereHas('coa', fn ($q) => $q->where('kategori', 'beban')->where('kode_akun', '!=', '5104'))
            ->whereHas('jurnal', $filterJurnal)
            ->sum('jumlah');

        return $pendapatan - $hpp - $bebanLain;
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