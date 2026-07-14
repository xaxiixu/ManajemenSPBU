<?php

namespace App\Http\Controllers;

use App\Models\Absensis;
use App\Models\Lembur;
use App\Models\ShiftMaster;
use App\Models\User;
use App\Notifications\PengajuanDiajukan;
use App\Notifications\PengajuanDiputuskan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

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

        Notification::send(
            User::whereIn('role', ['pengawas', 'manager'])->get(),
            new PengajuanDiajukan(
                jenis: 'lembur',
                namaPetugas: auth()->user()->name,
                tanggalLabel: Carbon::parse($validated['tanggal'])->format('d/m/Y'),
                url: route('approval.index'),
            )
        );

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

    // AJAX: dipanggil form saat petugas pilih tanggal, buat preview jam_mulai
    // (readonly) yang otomatis mengikuti jam_selesai shift hari itu.
    public function jamMulaiTersedia(Request $request)
    {
        $validated = $request->validate(['tanggal' => 'required|date']);

        $jamMulai = $this->jamMulaiDariShift(auth()->id(), $validated['tanggal']);

        if (!$jamMulai) {
            return response()->json(['ditemukan' => false]);
        }

        return response()->json([
            'ditemukan' => true,
            'shift'     => $jamMulai['shift'],
            'jam_mulai' => $jamMulai['jam_mulai'],
        ]);
    }

    // tanggal di sini adalah tanggal OPERASIONAL (sama dengan tanggal absensi
    // shift-nya) - bukan tanggal kalender jam_mulai lembur sungguhan. Konsisten
    // dengan tanggal presensi shift lintas tengah malam (mis. shift Malam yang
    // dimulai 7 Juli tetap tercatat tanggal 7 Juli walau berakhir jam 07:00 tgl 8).
    private function jamMulaiDariShift(int $userId, string $tanggal): ?array
    {
        $absensi = Absensis::where('user_id', $userId)
            ->whereDate('tanggal', $tanggal)
            ->where('status_hadir', 'hadir')
            ->first();

        if (!$absensi) {
            return null;
        }

        $shiftMaster = ShiftMaster::where('shift', $absensi->shift)->first();

        if (!$shiftMaster) {
            return null;
        }

        return [
            'shift'     => $absensi->shift,
            'jam_mulai' => substr($shiftMaster->jam_selesai, 0, 5),
        ];
    }

    private function validateForm(Request $request): array
    {
        $validated = $request->validate([
            'tanggal'     => [
                'required',
                'date',
                // BUKAN after_or_equal:today literal - today() adalah tanggal
                // kalender, tapi tanggal lembur mengikuti tanggal OPERASIONAL
                // absensi (shift lintas tengah malam bisa "milik" kemarin
                // walau kalender sudah berganti hari). Validasi utama yang
                // sebenarnya penting adalah absensi hadir wajib ada (dicek di
                // bawah) - rule ini cuma jaring pengaman longgar supaya tidak
                // bisa mengajukan lembur untuk tanggal yang terlalu lampau.
                function ($attribute, $value, $fail) {
                    $batasMundur = ShiftMaster::tanggalOperasionalSaatIni()['tanggal']->copy()->subDays(2);
                    if (Carbon::parse($value)->lessThan($batasMundur)) {
                        $fail('Tanggal lembur tidak boleh lebih dari 2 hari yang lalu.');
                    }
                },
            ],
            'jam_selesai' => 'required|date_format:H:i',
            'alasan'      => 'required|string|max:255',
        ]);

        // jam_mulai TIDAK pernah dipercaya dari input form (form hanya
        // menampilkannya sebagai preview readonly) - selalu diambil ulang dari
        // absensi + shift_master di server.
        $jamMulaiInfo = $this->jamMulaiDariShift(auth()->id(), $validated['tanggal']);

        if (!$jamMulaiInfo) {
            throw ValidationException::withMessages([
                'tanggal' => 'Anda belum tercatat hadir pada tanggal ini, tidak bisa mengajukan lembur.',
            ]);
        }

        $jamMulai = $jamMulaiInfo['jam_mulai'];

        // Durasi HARUS persis 1/2/3/4 jam (dropdown di form cuma kasih 4
        // pilihan itu) - cegah manipulasi request langsung yang kirim durasi
        // lain (mis. 90 menit). Lintas tengah malam dianggap lanjut ke hari
        // berikutnya, sama seperti Lembur::getDurasiMenitAttribute().
        $mulai   = Carbon::parse($jamMulai);
        $selesai = Carbon::parse($validated['jam_selesai']);
        if ($selesai->lessThanOrEqualTo($mulai)) {
            $selesai->addDay();
        }

        // diffInMinutes() Carbon mengembalikan float (mis. 60.0), bukan int -
        // cast dulu supaya perbandingan strict in_array() tidak salah menolak
        // durasi yang sebenarnya sudah tepat (60.0 !== 60 secara strict type).
        $durasiMenit = (int) $mulai->diffInMinutes($selesai);

        if (!in_array($durasiMenit, [60, 120, 180, 240], true)) {
            throw ValidationException::withMessages([
                'jam_selesai' => 'Durasi lembur harus tepat 1, 2, 3, atau 4 jam.',
            ]);
        }

        $validated['jam_mulai'] = $jamMulai;

        return $validated;
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

        $lembur->user->notify(new PengajuanDiputuskan(
            jenis: 'lembur',
            status: 'approved',
            tanggalLabel: $lembur->tanggal->format('d/m/Y'),
            url: route('lembur.index'),
        ));

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

        $lembur->user->notify(new PengajuanDiputuskan(
            jenis: 'lembur',
            status: 'rejected',
            tanggalLabel: $lembur->tanggal->format('d/m/Y'),
            url: route('lembur.index'),
        ));

        return back()->with('success', 'Pengajuan lembur ditolak.');
    }
}
