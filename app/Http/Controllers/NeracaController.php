<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;

class NeracaController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->format('Y-m-d');

        // Neraca = snapshot per satu tanggal, bukan per periode - semua saldo
        // dihitung dari awal (all-time) sampai tanggal terpilih, bukan
        // dibatasi bulan/rentang seperti Laporan Laba Rugi.
        $aset = Coa::where('kategori', 'aset')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get()
            ->map(function ($coa) use ($tanggal) {
                $coa->saldo = $this->saldoAkun($coa->id, $tanggal, debitPositif: true);
                return $coa;
            });

        $kewajiban = Coa::where('kategori', 'kewajiban')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get()
            ->map(function ($coa) use ($tanggal) {
                $coa->saldo = $this->saldoAkun($coa->id, $tanggal, debitPositif: false);
                return $coa;
            });

        $modal = Coa::where('kategori', 'modal')
            ->where('is_aktif', 1)
            ->orderBy('kode_akun')
            ->get()
            ->map(function ($coa) use ($tanggal) {
                $coa->saldo = $this->saldoAkun($coa->id, $tanggal, debitPositif: false);
                return $coa;
            });

        $totalAset      = $aset->sum('saldo');
        $totalKewajiban = $kewajiban->sum('saldo');
        $totalModalAkun = $modal->sum('saldo');

        // Laba/Rugi berjalan: sama pola seperti LaporanLabaRugiController,
        // tapi ALL-TIME sampai tanggal terpilih (tanpa filter bulan) - ini
        // menyambung ekuitas pemilik dengan hasil usaha yang belum "ditutup"
        // ke akun 3102 Laba Ditahan.
        $totalPendapatan = JurnalDetail::where('posisi', 'kredit')
            ->whereHas('coa', fn ($q) => $q->where('kategori', 'pendapatan'))
            ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal', '<=', $tanggal))
            ->sum('jumlah');

        $totalBeban = JurnalDetail::where('posisi', 'debit')
            ->whereHas('coa', fn ($q) => $q->where('kategori', 'beban'))
            ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal', '<=', $tanggal))
            ->sum('jumlah');

        $labaBerjalan = $totalPendapatan - $totalBeban;

        $totalModal  = $totalModalAkun + $labaBerjalan;
        $totalPasiva = $totalKewajiban + $totalModal;
        $selisih     = $totalAset - $totalPasiva;

        return view('neraca.index', compact(
            'aset', 'kewajiban', 'modal',
            'totalAset', 'totalKewajiban', 'totalModalAkun', 'labaBerjalan', 'totalModal', 'totalPasiva',
            'selisih', 'tanggal'
        ));
    }

    // Saldo akun dari awal s/d $tanggal (inklusif). Aset: debit - kredit.
    // Kewajiban & Modal: kredit - debit (posisi normal masing-masing).
    private function saldoAkun(int $coaId, string $tanggal, bool $debitPositif): int
    {
        $debit = JurnalDetail::where('coa_id', $coaId)
            ->where('posisi', 'debit')
            ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal', '<=', $tanggal))
            ->sum('jumlah');

        $kredit = JurnalDetail::where('coa_id', $coaId)
            ->where('posisi', 'kredit')
            ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal', '<=', $tanggal))
            ->sum('jumlah');

        return $debitPositif ? $debit - $kredit : $kredit - $debit;
    }
}
