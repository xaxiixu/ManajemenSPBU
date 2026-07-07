<?php

namespace App\Http\Controllers;

use App\Models\PengajuanShift;
use Illuminate\Http\Request;

class PengajuanShiftController extends Controller
{
    // Petugas: form pengajuan + daftar pengajuan miliknya
    public function index()
    {
        $data = PengajuanShift::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('pengajuan-shift.index', compact('data'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_diminta'   => 'required|in:Pagi,Siang,Malam',
            'tanggal_berlaku' => 'required|date|after_or_equal:today',
            'alasan'          => 'required|string|max:255',
        ]);

        PengajuanShift::create($validated + [
            'user_id' => auth()->id(),
            'status'  => 'pending',
        ]);

        return redirect()->route('pengajuan-shift.index')
            ->with('success', 'Pengajuan shift berhasil dikirim, menunggu approval.');
    }

    // Pengawas/Manager/IT
    public function approve(Request $request, PengajuanShift $pengajuanShift)
    {
        $validated = $request->validate(['catatan_approval' => 'nullable|string|max:255']);

        $pengajuanShift->update([
            'status'           => 'approved',
            'disetujui_oleh'   => auth()->id(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Pengajuan shift disetujui.');
    }

    public function reject(Request $request, PengajuanShift $pengajuanShift)
    {
        $validated = $request->validate(['catatan_approval' => 'nullable|string|max:255']);

        $pengajuanShift->update([
            'status'           => 'rejected',
            'disetujui_oleh'   => auth()->id(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Pengajuan shift ditolak.');
    }
}
