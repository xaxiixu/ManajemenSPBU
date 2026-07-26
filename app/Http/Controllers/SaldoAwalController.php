<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use App\Models\TangkiBbm;
use App\Services\JurnalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaldoAwalController extends Controller
{
    // Kalau sudah pernah diinput, tampilkan ringkasan read-only. Kalau
    // belum, arahkan ke form create - halaman ini tidak pernah menampilkan
    // form sendiri.
    public function index()
    {
        $jurnal = JurnalUmum::with(['details.coa', 'dibuatOleh'])
            ->where('sumber', 'saldo_awal')
            ->first();

        if (! $jurnal) {
            return redirect()->route('saldo-awal.create');
        }

        return view('saldo-awal.index', compact('jurnal'));
    }

    public function create()
    {
        // One-time: kalau sudah pernah diinput, jangan tampilkan form lagi -
        // lempar ke ringkasan read-only.
        if (JurnalUmum::where('sumber', 'saldo_awal')->exists()) {
            return redirect()->route('saldo-awal.index');
        }

        $tangkis = TangkiBbm::where('is_aktif', 1)->with('masterBbm')->orderBy('nama_tangki')->get();

        return view('saldo-awal.create', compact('tangkis'));
    }

    public function store(Request $request)
    {
        // Proteksi one-time juga di store() (bukan cuma create()) - jaga-jaga
        // request langsung/curl yang melewati halaman create.
        $sudahAda = JurnalUmum::where('sumber', 'saldo_awal')->first();
        if ($sudahAda) {
            return back()->withErrors([
                'saldo_awal' => 'Saldo awal sudah pernah diatur pada '.$sudahAda->tanggal->format('d/m/Y').', tidak bisa diinput ulang.',
            ]);
        }

        $validated = $request->validate([
            'tanggal_saldo_awal' => 'required|date',
            'kas_awal' => 'required|integer|min:0',
            'tangki' => 'nullable|array',
            'tangki.*.stok_awal' => 'nullable|integer|min:0',
            'tangki.*.harga_awal' => 'nullable|integer|min:0',
        ]);

        $tangkis = TangkiBbm::where('is_aktif', 1)->with('masterBbm')->get();

        $tangkiData = $tangkis->map(function (TangkiBbm $tangki) use ($validated) {
            $input = $validated['tangki'][$tangki->id] ?? null;

            return [
                'tangki' => $tangki,
                'stok_awal' => (int) ($input['stok_awal'] ?? 0),
                'harga_awal' => (int) ($input['harga_awal'] ?? 0),
            ];
        })->all();

        try {
            DB::transaction(function () use ($validated, $tangkiData) {
                JurnalService::dariSaldoAwal(
                    $validated['tanggal_saldo_awal'],
                    (int) $validated['kas_awal'],
                    $tangkiData
                );

                // Titik mula pencatatan stok/HPP: SET langsung (bukan moving
                // average seperti PembelianBbm) karena belum ada histori
                // sebelumnya untuk dirata-ratakan.
                foreach ($tangkiData as $row) {
                    if ($row['stok_awal'] > 0 && $row['harga_awal'] > 0) {
                        $row['tangki']->update([
                            'stok_liter' => $row['stok_awal'],
                            'harga_pokok_rata2' => $row['harga_awal'],
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            return back()->withErrors(['saldo_awal' => $e->getMessage()])->withInput();
        }

        return redirect()->route('saldo-awal.index')
            ->with('success', 'Saldo awal berhasil dicatat.');
    }
}
