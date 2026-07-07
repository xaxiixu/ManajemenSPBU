<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use App\Models\Coa;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Storage;
    use App\Services\JurnalService;

    class PengeluaranController extends Controller
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

            $data  = Pengeluaran::with('coa')
                ->whereBetween('tanggal', [$dari, $sampai])
                ->latest('tanggal')
                ->get();

            $total = $data->sum('jumlah');

            return view('pengeluaran.index', compact('data', 'total', 'dari', 'sampai', 'bulan'));
        }

        public function create()
        {
            $coa = $this->bebanCoaOptions();

            return view('pengeluaran.create', compact('coa'));
        }

        public function store(Request $request)
        {
            $request->validate([
                'tanggal'          => 'required|date',
                'coa_id'           => 'required|exists:coa,id',
                'keterangan'       => 'required|string|max:255',
                'jumlah'           => 'required|numeric|min:1',
                'bukti_pembayaran' => 'nullable|image|max:2048',
            ]);

            $bukti = null;
            if ($request->hasFile('bukti_pembayaran')) {
                $bukti = $request->file('bukti_pembayaran')->store('pengeluaran/bukti', 'public');
            }

            $pengeluaran = Pengeluaran::create([
                'tanggal'          => $request->tanggal,
                'coa_id'           => $request->coa_id,
                'keterangan'       => $request->keterangan,
                'jumlah'           => $request->jumlah,
                'bukti_pembayaran' => $bukti,
                'dicatat_oleh'     => auth()->id(),
            ]);

            JurnalService::dariPengeluaran($pengeluaran);

            return redirect()->route('pengeluaran.index')
                ->with('success', 'Data pengeluaran berhasil disimpan.');
        }

        public function show(Pengeluaran $pengeluaran)
        {
            return view('pengeluaran.show', compact('pengeluaran'));
        }

        public function edit(Pengeluaran $pengeluaran)
    {
        $coa = $this->bebanCoaOptions();

        return view('pengeluaran.edit', compact('pengeluaran', 'coa'));
    }

    // Akun beban top-level (parent atau standalone) beserta children aktifnya,
    // agar view bisa menampilkan parent sebagai grup non-selectable dan child terindentasi.
    private function bebanCoaOptions()
    {
        return Coa::where('kategori', 'beban')
            ->where('is_aktif', 1)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_aktif', 1)->orderBy('kode_akun');
            }])
            ->orderBy('kode_akun')
            ->get();
    }

    public function update(Request $request, Pengeluaran $pengeluaran)
    {
        $request->validate([
            'tanggal'          => 'required|date',
            'coa_id'           => 'required|exists:coa,id',
            'keterangan'       => 'required|string|max:255',
            'jumlah'           => 'required|numeric|min:1',
            'bukti_pembayaran' => 'nullable|image|max:2048',
        ]);

        $bukti = $pengeluaran->bukti_pembayaran;
        if ($request->hasFile('bukti_pembayaran')) {
            if ($bukti) {
                Storage::disk('public')->delete($bukti);
            }
            $bukti = $request->file('bukti_pembayaran')->store('pengeluaran/bukti', 'public');
        }

        $pengeluaran->update([
            'tanggal'          => $request->tanggal,
            'coa_id'           => $request->coa_id,
            'keterangan'       => $request->keterangan,
            'jumlah'           => $request->jumlah,
            'bukti_pembayaran' => $bukti,
        ]);

        return redirect()->route('pengeluaran.index')
            ->with('success', 'Data pengeluaran berhasil diupdate.');
    }

    public function destroy(Pengeluaran $pengeluaran)
    {
        if ($pengeluaran->bukti_pembayaran) {
            Storage::disk('public')->delete($pengeluaran->bukti_pembayaran);
        }
        $pengeluaran->delete();

        return redirect()->route('pengeluaran.index')
            ->with('success', 'Data pengeluaran berhasil dihapus.');
    }
}