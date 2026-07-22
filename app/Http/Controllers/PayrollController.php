<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use App\Models\PayrollDetail;
use App\Models\PayrollPenyesuaian;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Services\JurnalService;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll) {}

    /**
     * Daftar periode (saat ini + beberapa ke belakang) beserta status run-nya,
     * supaya manager bisa generate periode berjalan maupun periode terlewat.
     */
    public function index()
    {
        $setting = PayrollSetting::get();
        $kandidat = $this->payroll->daftarKandidatPeriode(6, $setting);

        return view('penggajian.index', compact('kandidat', 'setting'));
    }

    /**
     * Generate / regenerate draft untuk satu periode.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'periode_mulai' => 'required|date',
            'periode_selesai' => 'required|date|after_or_equal:periode_mulai',
        ]);

        try {
            $run = $this->payroll->generateDraft(
                Carbon::parse($validated['periode_mulai']),
                Carbon::parse($validated['periode_selesai']),
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('penggajian.index')->with('error', $e->getMessage());
        }

        return redirect()->route('penggajian.show', $run)
            ->with('success', 'Draft payroll berhasil dibuat. Silakan review sebelum dikirim.');
    }

    /**
     * Detail satu payroll run (draft = bisa diedit, dikirim = read-only).
     */
    public function show(PayrollRun $payrollRun)
    {
        $payrollRun->load(['details' => fn ($q) => $q->orderBy('id'), 'details.user', 'details.penyesuaian', 'dibuatOleh', 'dikirimOleh']);

        // Rekap total keseluruhan run (untuk footer & export)
        $rekap = [
            'gaji_pokok' => $payrollRun->details->sum('gaji_pokok_prorate'),
            'potongan' => $payrollRun->details->sum(fn ($d) => $d->potongan_telat + $d->potongan_absen),
            'lembur' => $payrollRun->details->sum('uang_lembur'),
            'penyesuaian' => $payrollRun->details->sum('total_penyesuaian'),
            'bersih' => $payrollRun->details->sum('total_gaji_bersih'),
        ];

        // Jurnal akuntansi terkait (dibuat saat run dikirim) — untuk jejak audit.
        $jurnal = $payrollRun->isDikirim()
            ? JurnalUmum::where('sumber', 'payroll')->where('referensi_id', $payrollRun->id)->first()
            : null;

        return view('penggajian.show', compact('payrollRun', 'rekap', 'jurnal'));
    }

    /**
     * Hapus draft (beserta detail & penyesuaian). Tidak untuk run yang dikirim.
     */
    public function destroy(PayrollRun $payrollRun)
    {
        if ($payrollRun->isDikirim()) {
            return redirect()->route('penggajian.index')
                ->with('error', 'Payroll yang sudah dikirim tidak bisa dihapus.');
        }

        $payrollRun->delete();

        return redirect()->route('penggajian.index')
            ->with('success', 'Draft payroll berhasil dihapus.');
    }

    /**
     * Kirim slip gaji: kunci run jadi final. Aksi tidak bisa dibatalkan.
     */
    public function kirim(PayrollRun $payrollRun)
    {
        if ($payrollRun->isDikirim()) {
            return redirect()->route('penggajian.show', $payrollRun)
                ->with('error', 'Payroll ini sudah pernah dikirim.');
        }

        try {
            DB::transaction(function () use ($payrollRun) {
                // 1) Kunci run jadi final.
                $payrollRun->update([
                    'status' => 'dikirim',
                    'dikirim_oleh' => auth()->id(),
                    'dikirim_pada' => now(),
                ]);

                // 2) Catat beban gaji ke jurnal akuntansi. Dilempar exception kalau
                //    akun COA tidak lengkap → seluruh transaksi (termasuk perubahan
                //    status di atas) ikut rollback, jadi tidak ada payroll "dikirim"
                //    tanpa jurnal. Idempotent terhadap percobaan kirim ulang.
                JurnalService::dariPayroll($payrollRun->fresh());
            });
        } catch (\Throwable $e) {
            return redirect()->route('penggajian.show', $payrollRun)
                ->with('error', 'Gagal mengirim slip gaji: '.$e->getMessage());
        }

        return redirect()->route('penggajian.show', $payrollRun)
            ->with('success', 'Slip gaji berhasil dikirim & beban gaji tercatat di jurnal. Data periode ini kini terkunci.');
    }

    // ── Penyesuaian manual (hanya saat run masih draft) ───────────────────

    private function pastikanDraft(PayrollDetail $detail): void
    {
        if (! $detail->payrollRun->isDraft()) {
            abort(403, 'Penyesuaian hanya bisa diubah selama payroll masih draft.');
        }
    }

    public function storePenyesuaian(Request $request, PayrollDetail $detail)
    {
        $this->pastikanDraft($detail);

        $validated = $request->validate([
            'keterangan' => 'required|string|max:255',
            'tipe' => 'required|in:bonus,potongan',
            'nominal' => 'required|integer|min:1',
        ]);

        // Simpan jumlah bertanda: bonus positif, potongan negatif.
        $jumlah = $validated['tipe'] === 'potongan'
            ? -abs($validated['nominal'])
            : abs($validated['nominal']);

        DB::transaction(function () use ($detail, $validated, $jumlah) {
            $detail->penyesuaian()->create([
                'keterangan' => $validated['keterangan'],
                'jumlah' => $jumlah,
                'dibuat_oleh' => auth()->id(),
            ]);

            // Hitung ulang total penyesuaian & gaji bersih detail ini.
            $detail->refreshTotal();
        });

        // Kembali ke halaman detail, buka lagi baris yang sedang diedit.
        return redirect()->to(route('penggajian.show', $detail->payroll_run_id).'?edit='.$detail->id)
            ->with('success', 'Penyesuaian ditambahkan.');
    }

    public function destroyPenyesuaian(PayrollPenyesuaian $penyesuaian)
    {
        $detail = $penyesuaian->payrollDetail;
        $this->pastikanDraft($detail);

        DB::transaction(function () use ($penyesuaian, $detail) {
            $penyesuaian->delete();
            $detail->refreshTotal();
        });

        return redirect()->to(route('penggajian.show', $detail->payroll_run_id).'?edit='.$detail->id)
            ->with('success', 'Penyesuaian dihapus.');
    }

    // ── Petugas: slip gaji milik sendiri ──────────────────────────────────

    /**
     * Riwayat slip gaji petugas yang login. HANYA run berstatus 'dikirim'
     * (draft belum final, tidak boleh terlihat petugas) dan HANYA miliknya.
     */
    public function gajiSaya()
    {
        $slip = PayrollDetail::with('payrollRun')
            ->where('user_id', auth()->id())
            ->whereHas('payrollRun', fn ($q) => $q->where('status', 'dikirim'))
            ->get()
            ->sortByDesc(fn ($d) => $d->payrollRun->periode_selesai)
            ->values();

        return view('gaji-saya.index', compact('slip'));
    }

    /**
     * Detail satu slip. Ownership check ketat: harus milik petugas yang login
     * dan run-nya sudah dikirim.
     */
    public function gajiSayaShow(PayrollDetail $payrollDetail)
    {
        abort_if($payrollDetail->user_id !== auth()->id(), 403, 'Ini bukan slip gaji Anda.');

        $payrollDetail->load(['payrollRun', 'penyesuaian', 'user']);

        abort_unless($payrollDetail->payrollRun->isDikirim(), 403, 'Slip gaji belum diterbitkan.');

        return view('gaji-saya.show', compact('payrollDetail'));
    }
}
