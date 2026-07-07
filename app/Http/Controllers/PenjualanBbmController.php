<?php

namespace App\Http\Controllers;

use App\Services\JurnalService;
use App\Models\PenjualanBbm;
use App\Models\Absensis;
use App\Models\MasterBbm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class PenjualanBbmController extends Controller
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

        $shift    = $request->shift;
        $jenisBbm = $request->jenis_bbm;

        $data = PenjualanBbm::with(['absensis.user', 'coa'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->when($shift, fn ($q) => $q->where('shift', $shift))
            ->when($jenisBbm, fn ($q) => $q->where('jenis_bbm', $jenisBbm))
            ->latest('tanggal')
            ->get();

        $jenisBbmOptions = MasterBbm::orderBy('jenis_bbm')->pluck('jenis_bbm');

        return view('penjualan-bbm.index', compact('data', 'dari', 'sampai', 'bulan', 'shift', 'jenisBbm', 'jenisBbmOptions'));
    }

    public function create()
    {
        $jenisBbmOptions = MasterBbm::where('is_aktif', 1)->orderBy('jenis_bbm')->get();

        return view('penjualan-bbm.create', compact('jenisBbmOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal'          => 'required|date',
            'shift'            => 'required|in:Pagi,Siang,Malam',
            'operator_id'      => 'required|exists:users,id',
            'pulau'            => 'required|string|max:10',
            'jenis_bbm'        => 'required|string|exists:master_bbm,jenis_bbm',
            'meter_awal'       => 'required|numeric|min:0',
            'meter_akhir'      => 'required|numeric|gt:meter_awal',
            'foto_meter_awal'  => 'required|image|max:2048',
            'foto_meter_akhir' => 'required|image|max:2048',
            'catatan'          => 'nullable|string',
        ], [
            'meter_akhir.gt'            => 'Meter akhir harus lebih besar dari meter awal.',
            'foto_meter_awal.required'  => 'Foto meter awal wajib diupload.',
            'foto_meter_akhir.required' => 'Foto meter akhir wajib diupload.',
            'operator_id.required'      => 'Pilih operator yang bertugas.',
            'jenis_bbm.exists'          => 'Jenis BBM tidak valid.',
        ]);

        // Harga, RON, dan akun COA TIDAK dipercaya dari input form (form hanya
        // menampilkannya untuk auto-fill/preview) - selalu diambil ulang dari
        // master_bbm di server supaya harga tidak bisa dimanipulasi lewat request.
        $masterBbm = MasterBbm::where('jenis_bbm', $validated['jenis_bbm'])
            ->where('is_aktif', 1)
            ->first();

        if (!$masterBbm) {
            return back()
                ->withErrors(['jenis_bbm' => 'Jenis BBM tidak aktif atau tidak ditemukan.'])
                ->withInput();
        }

        // Validasi ketat: operator yang dipilih harus benar-benar tercatat
        // hadir pada tanggal & shift ini - jangan percaya isi dropdown begitu
        // saja karena request bisa dimanipulasi langsung (mis. lewat curl/Postman).
        $absensi = Absensis::where('user_id', $validated['operator_id'])
            ->whereDate('tanggal', $validated['tanggal'])
            ->where('shift', $validated['shift'])
            ->where('status_hadir', 'hadir')
            ->first();

        if (!$absensi) {
            return back()
                ->withErrors(['operator_id' => 'Belum ada petugas yang absen hadir di tanggal & shift ini. Penjualan hanya bisa diinput untuk petugas yang sudah tercatat hadir.'])
                ->withInput();
        }

        $fotoAwal  = $request->file('foto_meter_awal')->store('penjualan/meter', 'public');
        $fotoAkhir = $request->file('foto_meter_akhir')->store('penjualan/meter', 'public');

        $penjualan = PenjualanBbm::create([
            'tanggal'           => $validated['tanggal'],
            'shift'             => $validated['shift'],
            'absensis_id'       => $absensi->id,
            'pulau'             => $validated['pulau'],
            'jenis_bbm'         => $masterBbm->jenis_bbm,
            'ron'               => $masterBbm->ron,
            'coa_pendapatan_id' => $masterBbm->coa_pendapatan_id,
            'meter_awal'        => $validated['meter_awal'],
            'meter_akhir'       => $validated['meter_akhir'],
            'harga_per_liter'   => $masterBbm->harga_per_liter,
            'foto_meter_awal'   => $fotoAwal,
            'foto_meter_akhir'  => $fotoAkhir,
            'catatan'           => $validated['catatan'] ?? null,
            'dicatat_oleh'      => auth()->id(),
        ]);

        JurnalService::dariPenjualan($penjualan);

        return redirect()->route('penjualan-bbm.index')
            ->with('success', 'Data penjualan berhasil disimpan.');
    }

    // AJAX: daftar petugas yang tercatat hadir pada tanggal & shift tertentu,
    // dipakai untuk mengisi dropdown operator di form create secara dinamis.
    public function operatorTersedia(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'shift'   => 'required|in:Pagi,Siang,Malam',
        ]);

        $operators = Absensis::with('user')
            ->whereDate('tanggal', $validated['tanggal'])
            ->where('shift', $validated['shift'])
            ->where('status_hadir', 'hadir')
            ->whereHas('user', fn ($q) => $q->where('role', 'petugas'))
            ->get()
            ->map(fn ($absensi) => ['id' => $absensi->user_id, 'name' => $absensi->user->name])
            ->values();

        return response()->json($operators);
    }

    public function show(PenjualanBbm $penjualanBbm)
    {
        return view('penjualan-bbm.show', compact('penjualanBbm'));
    }

    public function destroy(PenjualanBbm $penjualanBbm)
    {
        if ($penjualanBbm->foto_meter_awal) {
            Storage::disk('public')->delete($penjualanBbm->foto_meter_awal);
        }
        if ($penjualanBbm->foto_meter_akhir) {
            Storage::disk('public')->delete($penjualanBbm->foto_meter_akhir);
        }
        $penjualanBbm->delete();

        return redirect()->route('penjualan-bbm.index')
            ->with('success', 'Data berhasil dihapus.');
    }
}