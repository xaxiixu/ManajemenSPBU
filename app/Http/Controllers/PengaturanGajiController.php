<?php

namespace App\Http\Controllers;

use App\Models\PayrollSetting;
use Illuminate\Http\Request;

class PengaturanGajiController extends Controller
{
    /**
     * Form pengaturan payroll global (single row). Akses manager
     * (route sudah di-guard role:manager di web.php).
     */
    public function edit()
    {
        $setting = PayrollSetting::get();

        return view('pengaturan-gaji.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'tanggal_gajian'                => 'required|integer|min:1|max:31',
            'rate_lembur_per_jam'           => 'required|integer|min:0',
            'rate_potongan_telat_per_menit' => 'required|integer|min:0',
            'toleransi_telat_menit'         => 'required|integer|min:0',
            'kuota_izin_sakit_per_bulan'    => 'required|integer|min:0',
        ], [
            'tanggal_gajian.min' => 'Tanggal gajian harus antara 1-31.',
            'tanggal_gajian.max' => 'Tanggal gajian harus antara 1-31.',
        ]);

        $setting = PayrollSetting::get();
        $setting->update($validated);

        return redirect()->route('pengaturan-gaji.edit')
            ->with('success', 'Pengaturan penggajian berhasil disimpan.');
    }
}
