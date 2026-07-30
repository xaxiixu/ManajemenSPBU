<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;

class BukuBesarController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->bulan ?? now()->format('Y-m');
        $coaId = $request->coa_id;
        $coas = Coa::where('is_aktif', 1)->orderBy('kode_akun')->get();

        $data = collect();
        $saldoAwal = 0;
        $saldoAkhir = 0;
        $selectedCoa = $coaId ? Coa::find($coaId) : null;

        if ($coaId) {
            $tanggalMulai = substr($bulan, 0, 4).'-'.substr($bulan, 5, 2).'-01';
            $posisiNormal = $selectedCoa->posisi_normal;

            // ── Saldo Awal (carry-in): akumulasi semua transaksi akun ini
            // sebelum tanggal mulai periode filter ─────────────────────
            $debitSebelum = JurnalDetail::where('coa_id', $coaId)
                ->where('posisi', 'debit')
                ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal', '<', $tanggalMulai))
                ->sum('jumlah');

            $kreditSebelum = JurnalDetail::where('coa_id', $coaId)
                ->where('posisi', 'kredit')
                ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal', '<', $tanggalMulai))
                ->sum('jumlah');

            $saldoAwal = $posisiNormal == 'debit'
                ? $debitSebelum - $kreditSebelum
                : $kreditSebelum - $debitSebelum;

            // ── Transaksi periode berjalan, diurutkan berdasarkan tanggal
            // jurnal (kronologis transaksi), bukan kapan datanya diinput ─
            $data = JurnalDetail::with(['jurnal', 'coa'])
                ->where('coa_id', $coaId)
                ->whereHas('jurnal', function ($q) use ($bulan) {
                    $q->whereYear('tanggal', substr($bulan, 0, 4))
                        ->whereMonth('tanggal', substr($bulan, 5, 2));
                })
                ->join('jurnal_umum', 'jurnal_detail.jurnal_id', '=', 'jurnal_umum.id')
                ->orderBy('jurnal_umum.tanggal')
                ->orderBy('jurnal_detail.created_at')
                ->select('jurnal_detail.*')
                ->get();

            $totalDebitPeriode = $data->where('posisi', 'debit')->sum('jumlah');
            $totalKreditPeriode = $data->where('posisi', 'kredit')->sum('jumlah');

            $pergerakan = $posisiNormal == 'debit'
                ? $totalDebitPeriode - $totalKreditPeriode
                : $totalKreditPeriode - $totalDebitPeriode;

            $saldoAkhir = $saldoAwal + $pergerakan;
        }

        $totalDebit = $data->where('posisi', 'debit')->sum('jumlah');
        $totalKredit = $data->where('posisi', 'kredit')->sum('jumlah');

        return view('buku-besar.index', compact(
            'data', 'coas', 'bulan', 'coaId',
            'totalDebit', 'totalKredit', 'selectedCoa',
            'saldoAwal', 'saldoAkhir'
        ));
    }
}
