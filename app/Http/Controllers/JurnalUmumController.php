<?php

namespace App\Http\Controllers;

use App\Models\JurnalDetail;
use App\Models\JurnalUmum;
use Illuminate\Http\Request;

class JurnalUmumController extends Controller
{
    public function index(Request $request)
    {
        $bulan  = $request->bulan;
        $sumber = $request->sumber ?: null;

        if ($bulan) {
            $dari   = \Carbon\Carbon::parse($bulan . '-01')->startOfMonth()->toDateString();
            $sampai = \Carbon\Carbon::parse($bulan . '-01')->endOfMonth()->toDateString();
        } else {
            $dari   = $request->dari   ?? today()->startOfMonth()->toDateString();
            $sampai = $request->sampai ?? today()->toDateString();
        }

        $data = JurnalUmum::with(['details.coa', 'dibuatOleh'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->when($sumber, fn ($query) => $query->where('sumber', $sumber))
            ->latest('tanggal')
            ->get();

        $totals = JurnalDetail::join('jurnal_umum', 'jurnal_umum.id', '=', 'jurnal_detail.jurnal_id')
            ->whereBetween('jurnal_umum.tanggal', [$dari, $sampai])
            ->when($sumber, fn ($query) => $query->where('jurnal_umum.sumber', $sumber))
            ->selectRaw("SUM(CASE WHEN jurnal_detail.posisi = 'debit' THEN jurnal_detail.jumlah ELSE 0 END) as total_debit")
            ->selectRaw("SUM(CASE WHEN jurnal_detail.posisi = 'kredit' THEN jurnal_detail.jumlah ELSE 0 END) as total_kredit")
            ->first();

        $totalDebit  = (float) ($totals->total_debit ?? 0);
        $totalKredit = (float) ($totals->total_kredit ?? 0);
        $selisih     = $totalDebit - $totalKredit;

        $sumberOptions = [
            'penjualan_bbm' => 'Penjualan BBM',
            'pengeluaran'   => 'Pengeluaran',
            'payroll'       => 'Gaji',
            'pembelian_bbm' => 'Pembelian BBM',
            'saldo_awal'    => 'Saldo Awal',
        ];

        return view('jurnal-umum.index', compact('data', 'dari', 'sampai', 'bulan', 'sumber', 'sumberOptions', 'totalDebit', 'totalKredit', 'selisih'));
    }
}