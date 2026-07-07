<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use Illuminate\Http\Request;

class JurnalUmumController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->bulan;

        if ($bulan) {
            $dari   = \Carbon\Carbon::parse($bulan . '-01')->startOfMonth()->toDateString();
            $sampai = \Carbon\Carbon::parse($bulan . '-01')->endOfMonth()->toDateString();
        } else {
            $dari   = $request->dari   ?? today()->startOfMonth()->toDateString();
            $sampai = $request->sampai ?? today()->toDateString();
        }

        $data = JurnalUmum::with(['details.coa', 'dibuatOleh'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->latest('tanggal')
            ->get();

        return view('jurnal-umum.index', compact('data', 'dari', 'sampai', 'bulan'));
    }

    public function show(JurnalUmum $jurnalUmum)
    {
        $jurnalUmum->load('details.coa');
        return view('jurnal-umum.show', compact('jurnalUmum'));
    }
}