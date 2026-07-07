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
        $validated = $this->validateForm($request);

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

    // Petugas: edit pengajuan miliknya sendiri, hanya selama masih pending
    public function edit(Lembur $lembur)
    {
        abort_if($lembur->user_id !== auth()->id(), 403, 'Anda tidak bisa mengakses pengajuan milik petugas lain.');
        abort_if($lembur->status !== 'pending', 403, "Pengajuan ini sudah diproses (status: {$lembur->status}), tidak bisa diedit lagi.");

        return view('lembur.edit', compact('lembur'));
    }

    public function update(Request $request, Lembur $lembur)
    {
        abort_if($lembur->user_id !== auth()->id(), 403, 'Anda tidak bisa mengubah pengajuan milik petugas lain.');
        abort_if($lembur->status !== 'pending', 403, "Pengajuan ini sudah diproses (status: {$lembur->status}), tidak bisa diedit lagi.");

        $validated = $this->validateForm($request);

        // Kecualikan baris ini sendiri dari cek duplikat tanggal, supaya edit
        // tanpa mengubah tanggal tidak salah dianggap bentrok dengan dirinya sendiri.
        if (Lembur::where('user_id', auth()->id())
            ->where('id', '!=', $lembur->id)
            ->whereDate('tanggal', $validated['tanggal'])
            ->exists()) {
            return back()
                ->withErrors(['tanggal' => 'Anda sudah mengajukan lembur untuk tanggal ini.'])
                ->withInput();
        }

        $lembur->update($validated);

        return redirect()->route('lembur.index')
            ->with('success', 'Pengajuan lembur berhasil diperbarui.');
    }

    // Petugas: batalkan pengajuan miliknya sendiri, hanya selama masih pending
    public function destroy(Lembur $lembur)
    {
        abort_if($lembur->user_id !== auth()->id(), 403, 'Anda tidak bisa menghapus pengajuan milik petugas lain.');
        abort_if($lembur->status !== 'pending', 403, "Pengajuan ini sudah diproses (status: {$lembur->status}), tidak bisa dibatalkan lagi.");

        $lembur->delete();

        return redirect()->route('lembur.index')
            ->with('success', 'Pengajuan lembur berhasil dibatalkan.');
    }

    private function validateForm(Request $request): array
    {
        return $request->validate([
            'tanggal'     => 'required|date|after_or_equal:today',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => [
                'required',
                'date_format:H:i',
                // Lembur SPBU umum lintas tengah malam (mis. 22:00-02:00), jadi
                // jam_selesai < jam_mulai itu valid (dianggap lanjut ke hari
                // berikutnya) - hanya tolak kalau persis sama (durasi nol).
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === $request->jam_mulai) {
                        $fail('Jam selesai tidak boleh sama dengan jam mulai.');
                    }
                },
            ],
            'alasan'      => 'required|string|max:255',
        ]);
    }

    // Pengawas/Manager/IT
    public function approve(Request $request, Lembur $lembur)
    {
        if ($lembur->status !== 'pending') {
            return back()->with('error', "Pengajuan ini sudah diproses sebelumnya (status: {$lembur->status}).");
        }

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
        if ($lembur->status !== 'pending') {
            return back()->with('error', "Pengajuan ini sudah diproses sebelumnya (status: {$lembur->status}).");
        }

        $validated = $request->validate(['catatan_approval' => 'nullable|string|max:255']);

        $lembur->update([
            'status'           => 'rejected',
            'disetujui_oleh'   => auth()->id(),
            'catatan_approval' => $validated['catatan_approval'] ?? null,
        ]);

        return back()->with('success', 'Pengajuan lembur ditolak.');
    }
}
