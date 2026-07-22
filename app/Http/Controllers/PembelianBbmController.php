<?php

namespace App\Http\Controllers;

use App\Models\PembelianBbm;
use App\Models\TangkiBbm;
use App\Services\JurnalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianBbmController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->bulan;

        if ($bulan) {
            $dari = \Carbon\Carbon::parse($bulan.'-01')->startOfMonth()->toDateString();
            $sampai = \Carbon\Carbon::parse($bulan.'-01')->endOfMonth()->toDateString();
        } else {
            $dari = $request->dari ?? today()->startOfMonth()->toDateString();
            $sampai = $request->sampai ?? today()->toDateString();
        }

        $data = PembelianBbm::with(['tangki.masterBbm', 'dicatatOleh'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->latest('tanggal')
            ->get();

        $total = $data->sum('subtotal');

        return view('pembelian-bbm.index', compact('data', 'total', 'dari', 'sampai', 'bulan'));
    }

    public function create()
    {
        $tangkis = TangkiBbm::where('is_aktif', 1)->with('masterBbm')->orderBy('nama_tangki')->get();

        return view('pembelian-bbm.create', compact('tangkis'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'nama_supplier' => 'required|string|max:150',
            'tangki_id' => 'required|exists:tangki_bbm,id',
            'volume_liter' => 'required|numeric|min:1',
            'harga_per_liter' => 'required|numeric|min:1',
            'status_bayar' => 'required|in:tunai,kredit',
        ]);

        // Insert pembelian + update stok/harga pokok tangki (model event) +
        // generate jurnal HARUS atomik: kalau salah satu gagal, semuanya batal.
        DB::transaction(function () use ($validated) {
            $pembelian = PembelianBbm::create([
                'tanggal' => $validated['tanggal'],
                'nama_supplier' => $validated['nama_supplier'],
                'tangki_id' => $validated['tangki_id'],
                'volume_liter' => $validated['volume_liter'],
                'harga_per_liter' => $validated['harga_per_liter'],
                'status_bayar' => $validated['status_bayar'],
                'dicatat_oleh' => auth()->id(),
            ]);

            JurnalService::dariPembelian($pembelian);
        });

        return redirect()->route('pembelian-bbm.index')
            ->with('success', 'Data pembelian BBM berhasil disimpan.');
    }

    public function show(PembelianBbm $pembelianBbm)
    {
        $pembelianBbm->load(['tangki.masterBbm', 'dicatatOleh']);

        return view('pembelian-bbm.show', compact('pembelianBbm'));
    }
}
