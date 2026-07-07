<?php

namespace App\Http\Controllers;

use App\Models\Lembur;
use Illuminate\Http\Request;

class LemburController extends Controller
{
    // Petugas: form pengajuan + daftar lembur miliknya
    public function index()
    {
        $data = Lembur::where('user_id', auth()->id())
            ->latest('tanggal')
            ->get();

        return view('lembur.index', compact('data'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal'     => 'required|date',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'alasan'      => 'required|string|max:255',
        ]);

        if (Lembur::where('user_id', auth()->id())->whereDate('tanggal', $validated['tanggal'])->exists()) {
            return back()
                ->withErrors(['tanggal' => 'Anda sudah mengajukan lembur untuk tanggal ini.'])
                ->withInput();
        }

        Lembur::create($validated + [
            'user_id' => auth()->id(),
            'status'  => 'pending',
        ]);

        return redirect()->route('lembur.index')
            ->with('success', 'Pengajuan lembur berhasil dikirim, menunggu approval.');
    }

    // Pengawas/Manager/IT
    public function approve(Request $request, Lembur $lembur)
    {
        $validated = $request->validate(['catatan_approval' => 'nullable|string|max:255']);

        $lembur->update([
            'status'           => 'approved',
            'disetujui_oleh'   => auth()->id(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Pengajuan lembur disetujui.');
    }

    public function reject(Request $request, Lembur $lembur)
    {
        $validated = $request->validate(['catatan_approval' => 'nullable|string|max:255']);

        $lembur->update([
            'status'           => 'rejected',
            'disetujui_oleh'   => auth()->id(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Pengajuan lembur ditolak.');
    }
}
