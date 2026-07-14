<?php

namespace App\Http\Controllers;

use App\Models\PengajuanShift;
use App\Models\User;
use App\Notifications\PengajuanDiajukan;
use App\Notifications\PengajuanDiputuskan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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

        if (PengajuanShift::where('user_id', auth()->id())->where('status', 'pending')->exists()) {
            return back()
                ->withErrors(['shift_diminta' => 'Anda masih punya pengajuan shift yang belum diproses. Selesaikan atau batalkan pengajuan sebelumnya terlebih dahulu.'])
                ->withInput();
        }

        PengajuanShift::create($validated + [
            'user_id' => auth()->id(),
            'status'  => 'pending',
        ]);

        Notification::send(
            User::whereIn('role', ['pengawas', 'manager'])->get(),
            new PengajuanDiajukan(
                jenis: 'shift',
                namaPetugas: auth()->user()->name,
                tanggalLabel: \Carbon\Carbon::parse($validated['tanggal_berlaku'])->format('d/m/Y'),
                url: route('approval.index'),
            )
        );

        return redirect()->route('pengajuan-shift.index')
            ->with('success', 'Pengajuan shift berhasil dikirim, menunggu approval.');
    }

    // Petugas: edit pengajuan miliknya sendiri, hanya selama masih pending
    public function edit(PengajuanShift $pengajuanShift)
    {
        abort_if($pengajuanShift->user_id !== auth()->id(), 403, 'Anda tidak bisa mengakses pengajuan milik petugas lain.');
        abort_if($pengajuanShift->status !== 'pending', 403, "Pengajuan ini sudah diproses (status: {$pengajuanShift->status}), tidak bisa diedit lagi.");

        return view('pengajuan-shift.edit', compact('pengajuanShift'));
    }

    public function update(Request $request, PengajuanShift $pengajuanShift)
    {
        abort_if($pengajuanShift->user_id !== auth()->id(), 403, 'Anda tidak bisa mengubah pengajuan milik petugas lain.');
        abort_if($pengajuanShift->status !== 'pending', 403, "Pengajuan ini sudah diproses (status: {$pengajuanShift->status}), tidak bisa diedit lagi.");

        $validated = $request->validate([
            'shift_diminta'   => 'required|in:Pagi,Siang,Malam',
            'tanggal_berlaku' => 'required|date|after_or_equal:today',
            'alasan'          => 'required|string|max:255',
        ]);

        $pengajuanShift->update($validated);

        return redirect()->route('pengajuan-shift.index')
            ->with('success', 'Pengajuan shift berhasil diperbarui.');
    }

    // Petugas: batalkan pengajuan miliknya sendiri, hanya selama masih pending
    public function destroy(PengajuanShift $pengajuanShift)
    {
        abort_if($pengajuanShift->user_id !== auth()->id(), 403, 'Anda tidak bisa menghapus pengajuan milik petugas lain.');
        abort_if($pengajuanShift->status !== 'pending', 403, "Pengajuan ini sudah diproses (status: {$pengajuanShift->status}), tidak bisa dibatalkan lagi.");

        $pengajuanShift->delete();

        return redirect()->route('pengajuan-shift.index')
            ->with('success', 'Pengajuan shift berhasil dibatalkan.');
    }

    // Pengawas/Manager/IT
    public function approve(Request $request, PengajuanShift $pengajuanShift)
    {
        if ($pengajuanShift->status !== 'pending') {
            return back()->with('error', "Pengajuan ini sudah diproses sebelumnya (status: {$pengajuanShift->status}).");
        }

        $validated = $request->validate(['catatan_approval' => 'nullable|string|max:255']);

        $pengajuanShift->update([
            'status'           => 'approved',
            'disetujui_oleh'   => auth()->id(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        // Approval = kanal resmi untuk ubah shift petugas, jadi langsung
        // terapkan shift yang diminta ke shift_default user (kalau tidak,
        // approval tidak berefek apa-apa terhadap shift petugas ybs).
        $pengajuanShift->user()->update(['shift_default' => $pengajuanShift->shift_diminta]);

        $pengajuanShift->user->notify(new PengajuanDiputuskan(
            jenis: 'shift',
            status: 'approved',
            tanggalLabel: $pengajuanShift->tanggal_berlaku->format('d/m/Y'),
            url: route('pengajuan-shift.index'),
        ));

        return back()->with('success', 'Pengajuan shift disetujui.');
    }

    public function reject(Request $request, PengajuanShift $pengajuanShift)
    {
        if ($pengajuanShift->status !== 'pending') {
            return back()->with('error', "Pengajuan ini sudah diproses sebelumnya (status: {$pengajuanShift->status}).");
        }

        $validated = $request->validate(['catatan_approval' => 'nullable|string|max:255']);

        $pengajuanShift->update([
            'status'           => 'rejected',
            'disetujui_oleh'   => auth()->id(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        $pengajuanShift->user->notify(new PengajuanDiputuskan(
            jenis: 'shift',
            status: 'rejected',
            tanggalLabel: $pengajuanShift->tanggal_berlaku->format('d/m/Y'),
            url: route('pengajuan-shift.index'),
        ));

        return back()->with('success', 'Pengajuan shift ditolak.');
    }
}
