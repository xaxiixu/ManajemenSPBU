<?php

namespace App\Http\Controllers;

use App\Models\Lembur;
use App\Models\PengajuanShift;

class ApprovalController extends Controller
{
    public function index()
    {
        $pengajuanShift = PengajuanShift::with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $lembur = Lembur::with('user')
            ->where('status', 'pending')
            ->latest('tanggal')
            ->get();

        return view('approval.index', compact('pengajuanShift', 'lembur'));
    }
}
