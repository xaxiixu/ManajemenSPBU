<?php

namespace App\Services;

use App\Models\JurnalUmum;
use App\Models\JurnalDetail;
use App\Models\Coa;

class JurnalService
{
    // Dipanggil saat ada penjualan BBM baru
    public static function dariPenjualan($penjualan): void
    {
        // Akun Kas (1101)
        $akunKas = Coa::where('kode_akun', '1101')->value('id');

        // Akun Pendapatan sesuai jenis BBM
        $akunPendapatan = $penjualan->coa_pendapatan_id;

        if (!$akunKas || !$akunPendapatan) return;

        $jurnal = JurnalUmum::create([
            'nomor_jurnal' => JurnalUmum::generateNomor($penjualan->tanggal),
            'tanggal'      => $penjualan->tanggal,
            'keterangan'   => 'Penjualan ' . $penjualan->jenis_bbm . ' - Shift ' . $penjualan->shift . ' Nozzle ' . $penjualan->nozzle,
            'sumber'       => 'penjualan_bbm',
            'referensi_id' => $penjualan->id,
            'dibuat_oleh'  => auth()->id(),
        ]);

        // Debit: Kas bertambah
        JurnalDetail::create([
            'jurnal_id'  => $jurnal->id,
            'coa_id'     => $akunKas,
            'posisi'     => 'debit',
            'jumlah'     => $penjualan->total_penjualan,
            'keterangan' => 'Penerimaan kas penjualan BBM',
        ]);

        // Kredit: Pendapatan bertambah
        JurnalDetail::create([
            'jurnal_id'  => $jurnal->id,
            'coa_id'     => $akunPendapatan,
            'posisi'     => 'kredit',
            'jumlah'     => $penjualan->total_penjualan,
            'keterangan' => 'Pendapatan penjualan ' . $penjualan->jenis_bbm,
        ]);
    }

    // Dipanggil saat ada pengeluaran baru
    public static function dariPengeluaran($pengeluaran): void
    {
        // Akun Kas (1101)
        $akunKas = Coa::where('kode_akun', '1101')->value('id');
        $akunBeban = $pengeluaran->coa_id;

        if (!$akunKas || !$akunBeban) return;

        $jurnal = JurnalUmum::create([
            'nomor_jurnal' => JurnalUmum::generateNomor($pengeluaran->tanggal),
            'tanggal'      => $pengeluaran->tanggal,
            'keterangan'   => 'Pengeluaran: ' . $pengeluaran->keterangan,
            'sumber'       => 'pengeluaran',
            'referensi_id' => $pengeluaran->id,
            'dibuat_oleh'  => auth()->id(),
        ]);

        // Debit: Beban bertambah
        JurnalDetail::create([
            'jurnal_id'  => $jurnal->id,
            'coa_id'     => $akunBeban,
            'posisi'     => 'debit',
            'jumlah'     => $pengeluaran->jumlah,
            'keterangan' => $pengeluaran->keterangan,
        ]);

        // Kredit: Kas berkurang
        JurnalDetail::create([
            'jurnal_id'  => $jurnal->id,
            'coa_id'     => $akunKas,
            'posisi'     => 'kredit',
            'jumlah'     => $pengeluaran->jumlah,
            'keterangan' => 'Pembayaran ' . $pengeluaran->keterangan,
        ]);
    }
}