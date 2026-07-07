<?php

namespace App\Http\Controllers;

use App\Services\JurnalService;
use App\Models\PenjualanBbm;
use App\Models\Absensis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class PenjualanBbmController extends Controller
{
    public function index()
    {
        $data = PenjualanBbm::with(['absensis', 'coa'])
            ->latest('tanggal')
            ->paginate(15);

        return view('penjualan-bbm.index', compact('data'));
    }

    public function create()
    {
        return view('penjualan-bbm.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'          => 'required|date',
            'shift'            => 'required|in:Pagi,Siang,Malam',
            'pulau'            => 'required|string|max:10',
            'nozzle'           => 'required|string|max:10',
            'jenis_bbm'        => 'required|in:Pertalite,Pertamax,Solar',
            'meter_awal'       => 'required|numeric|min:0',
            'meter_akhir'      => 'required|numeric|gt:meter_awal',
            'harga_per_liter'  => 'required|numeric|min:1',
            'foto_meter_awal'  => 'required|image|max:2048',
            'foto_meter_akhir' => 'required|image|max:2048',
            'catatan'          => 'nullable|string',
        ], [
            'meter_akhir.gt'            => 'Meter akhir harus lebih besar dari meter awal.',
            'foto_meter_awal.required'  => 'Foto meter awal wajib diupload.',
            'foto_meter_akhir.required' => 'Foto meter akhir wajib diupload.',
        ]);

        $fotoAwal  = $request->file('foto_meter_awal')->store('penjualan/meter', 'public');
        $fotoAkhir = $request->file('foto_meter_akhir')->store('penjualan/meter', 'public');

        // Operator = petugas yang sedang login; absensis_id diambil dari sesi
        // presensinya pada tanggal & shift yang sama (kalau ada)
        $absensisId = Absensis::where('user_id', auth()->id())
            ->whereDate('tanggal', $request->tanggal)
            ->where('shift', $request->shift)
            ->value('id');

        $penjualan = PenjualanBbm::create([
            'tanggal'           => $request->tanggal,
            'shift'             => $request->shift,
            'absensis_id'       => $absensisId,
            'pulau'             => $request->pulau,
            'nozzle'            => $request->nozzle,
            'jenis_bbm'         => $request->jenis_bbm,
            'coa_pendapatan_id' => PenjualanBbm::coaByJenisBbm($request->jenis_bbm),
            'meter_awal'        => $request->meter_awal,
            'meter_akhir'       => $request->meter_akhir,
            'harga_per_liter'   => $request->harga_per_liter,
            'foto_meter_awal'   => $fotoAwal,
            'foto_meter_akhir'  => $fotoAkhir,
            'catatan'           => $request->catatan,
            'dicatat_oleh'      => auth()->id(),
        ]);

        JurnalService::dariPenjualan($penjualan);

        return redirect()->route('penjualan-bbm.index')
            ->with('success', 'Data penjualan berhasil disimpan.');
    }

    public function show(PenjualanBbm $penjualanBbm)
    {
        return view('penjualan-bbm.show', compact('penjualanBbm'));
    }

    public function destroy(PenjualanBbm $penjualanBbm)
    {
        if ($penjualanBbm->dicatat_oleh !== auth()->id() && auth()->user()->role !== 'it') {
            abort(403, 'Anda hanya bisa menghapus data penjualan milik sendiri.');
        }

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