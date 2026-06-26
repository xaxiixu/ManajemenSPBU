<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;

class BukuBesarController extends Controller
{
    public function index(Request $request)
    {
        $bulan   = $request->bulan ?? now()->format('Y-m');
        $coaId   = $request->coa_id;
        $coas    = Coa::where('is_aktif', 1)->orderBy('kode_akun')->get();

        $data = collect();

        if ($coaId) {
            $data = JurnalDetail::with(['jurnal', 'coa'])
                ->where('coa_id', $coaId)
                ->whereHas('jurnal', function($q) use ($bulan) {
                    $q->whereYear('tanggal', substr($bulan, 0, 4))
                      ->whereMonth('tanggal', substr($bulan, 5, 2));
                })
                ->orderBy('created_at')
                ->get();
        }

        $totalDebit  = $data->where('posisi', 'debit')->sum('jumlah');
        $totalKredit = $data->where('posisi', 'kredit')->sum('jumlah');
        $selectedCoa = $coaId ? Coa::find($coaId) : null;

        return view('buku-besar.index', compact(
            'data', 'coas', 'bulan', 'coaId',
            'totalDebit', 'totalKredit', 'selectedCoa'
        ));
    }
}