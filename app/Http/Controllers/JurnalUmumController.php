<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use Illuminate\Http\Request;

class JurnalUmumController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->bulan ?? now()->format('Y-m');

        $data = JurnalUmum::with(['details.coa', 'dibuatOleh'])
            ->whereYear('tanggal', substr($bulan, 0, 4))
            ->whereMonth('tanggal', substr($bulan, 5, 2))
            ->latest('tanggal')
            ->get();

        return view('jurnal-umum.index', compact('data', 'bulan'));
    }

    public function show(JurnalUmum $jurnalUmum)
    {
        $jurnalUmum->load('details.coa');
        return view('jurnal-umum.show', compact('jurnalUmum'));
    }
}