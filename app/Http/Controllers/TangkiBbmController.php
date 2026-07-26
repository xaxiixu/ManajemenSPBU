<?php

namespace App\Http\Controllers;

use App\Models\TangkiBbm;

class TangkiBbmController extends Controller
{
    public function index()
    {
        $tangkis = TangkiBbm::where('is_aktif', 1)->with('masterBbm')->orderBy('nama_tangki')->get();

        return view('tangki-bbm.index', compact('tangkis'));
    }
}
