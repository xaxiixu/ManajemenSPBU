<?php

namespace App\Services;

use App\Models\Coa;
use App\Models\JurnalDetail;
use App\Models\JurnalUmum;
use App\Models\PayrollRun;

class JurnalService
{
    // Dipanggil saat ada penjualan BBM baru
    public static function dariPenjualan($penjualan): void
    {
        // Akun Kas (1101)
        $akunKas = Coa::where('kode_akun', '1101')->value('id');

        // Akun Pendapatan sesuai jenis BBM
        $akunPendapatan = $penjualan->coa_pendapatan_id;

        if (! $akunKas || ! $akunPendapatan) {
            return;
        }

        $jurnal = JurnalUmum::create([
            'nomor_jurnal' => JurnalUmum::generateNomor($penjualan->tanggal),
            'tanggal' => $penjualan->tanggal,
            'keterangan' => 'Penjualan '.$penjualan->jenis_bbm.' - Shift '.$penjualan->shift.' Pulau '.$penjualan->pulau,
            'sumber' => 'penjualan_bbm',
            'referensi_id' => $penjualan->id,
            'dibuat_oleh' => auth()->id(),
        ]);

        // Debit: Kas bertambah
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunKas,
            'posisi' => 'debit',
            'jumlah' => $penjualan->total_penjualan,
            'keterangan' => 'Penerimaan kas penjualan BBM',
        ]);

        // Kredit: Pendapatan bertambah
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunPendapatan,
            'posisi' => 'kredit',
            'jumlah' => $penjualan->total_penjualan,
            'keterangan' => 'Pendapatan penjualan '.$penjualan->jenis_bbm,
        ]);

        // Baris HPP vs Persediaan (mengakui beban pokok penjualan & mengurangi
        // nilai persediaan pada jurnal yang SAMA) - HANYA kalau HPP-nya
        // diketahui (hpp_per_liter di-snapshot saat penjualan dibuat, lihat
        // PenjualanBbm::boot()) dan akun Persediaan jenis BBM ini resolvable
        // (via tangki->masterBbm->coa_persediaan_id). Kalau tidak (data lama
        // sebelum kolom ini ada, atau jenis BBM tanpa tangki terdaftar), jurnal
        // tetap 2 baris seperti sebelumnya - TIDAK memblokir pencatatan
        // pendapatan yang sudah pasti benar di atas.
        $hppPerLiter = $penjualan->hpp_per_liter;
        $akunPersediaan = $penjualan->tangki?->masterBbm?->coa_persediaan_id;

        if ($hppPerLiter === null || ! $akunPersediaan) {
            return;
        }

        $jumlahHpp = $penjualan->liter_terjual * $hppPerLiter;

        // Tidak ada nilai persediaan yang berpindah (mis. HPP masih 0 karena
        // tangki belum pernah dibeli) - tidak perlu baris jurnal senilai 0.
        if ($jumlahHpp <= 0) {
            return;
        }

        $akunHpp = Coa::where('kode_akun', '5104')->value('id');
        if (! $akunHpp) {
            return;
        }

        // Debit: HPP (beban) bertambah
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunHpp,
            'posisi' => 'debit',
            'jumlah' => $jumlahHpp,
            'keterangan' => 'HPP penjualan '.$penjualan->jenis_bbm,
        ]);

        // Kredit: Persediaan BBM berkurang
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunPersediaan,
            'posisi' => 'kredit',
            'jumlah' => $jumlahHpp,
            'keterangan' => 'Pengurangan stok '.$penjualan->jenis_bbm,
        ]);
    }

    // Dipanggil saat ada pengeluaran baru
    public static function dariPengeluaran($pengeluaran): void
    {
        // Akun Kas (1101)
        $akunKas = Coa::where('kode_akun', '1101')->value('id');
        $akunBeban = $pengeluaran->coa_id;

        if (! $akunKas || ! $akunBeban) {
            return;
        }

        $jurnal = JurnalUmum::create([
            'nomor_jurnal' => JurnalUmum::generateNomor($pengeluaran->tanggal),
            'tanggal' => $pengeluaran->tanggal,
            'keterangan' => 'Pengeluaran: '.$pengeluaran->keterangan,
            'sumber' => 'pengeluaran',
            'referensi_id' => $pengeluaran->id,
            'dibuat_oleh' => auth()->id(),
        ]);

        // Debit: Beban bertambah
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunBeban,
            'posisi' => 'debit',
            'jumlah' => $pengeluaran->jumlah,
            'keterangan' => $pengeluaran->keterangan,
        ]);

        // Kredit: Kas berkurang
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunKas,
            'posisi' => 'kredit',
            'jumlah' => $pengeluaran->jumlah,
            'keterangan' => 'Pembayaran '.$pengeluaran->keterangan,
        ]);
    }

    // Dipanggil saat ada pembelian BBM baru. Pola sama persis seperti
    // dariPenjualan()/dariPengeluaran() (silent no-op kalau akun COA belum
    // lengkap), sesuai permintaan eksplisit untuk mengikuti pola dariPenjualan().
    //
    // Akun Persediaan di-resolve DINAMIS per jenis BBM tangki (via
    // master_bbm.coa_persediaan_id, lihat Fase 1) - bukan hardcode kode_akun
    // tunggal, karena tiap jenis BBM punya akun persediaan sendiri (1104-1/2/3).
    // Akun kredit tergantung status_bayar: tunai -> Kas (1101), kredit -> Utang
    // Usaha (2101).
    public static function dariPembelian($pembelian): void
    {
        $tangki = $pembelian->tangki()->with('masterBbm')->first();
        $akunPersediaan = $tangki?->masterBbm?->coa_persediaan_id;

        $akunKredit = $pembelian->status_bayar === 'tunai'
            ? Coa::where('kode_akun', '1101')->value('id')   // Kas
            : Coa::where('kode_akun', '2101')->value('id');  // Utang Usaha

        if (! $akunPersediaan || ! $akunKredit) {
            return;
        }

        $jurnal = JurnalUmum::create([
            'nomor_jurnal' => JurnalUmum::generateNomor($pembelian->tanggal),
            'tanggal' => $pembelian->tanggal,
            'keterangan' => 'Pembelian BBM dari '.$pembelian->nama_supplier.' - '.($tangki->nama_tangki ?? ''),
            'sumber' => 'pembelian_bbm',
            'referensi_id' => $pembelian->id,
            'dibuat_oleh' => auth()->id(),
        ]);

        // Debit: Persediaan BBM bertambah
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunPersediaan,
            'posisi' => 'debit',
            'jumlah' => $pembelian->subtotal,
            'keterangan' => 'Penambahan stok '.($tangki->masterBbm->jenis_bbm ?? '').' dari '.$pembelian->nama_supplier,
        ]);

        // Kredit: Kas berkurang (tunai) atau Utang Usaha bertambah (kredit)
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunKredit,
            'posisi' => 'kredit',
            'jumlah' => $pembelian->subtotal,
            'keterangan' => $pembelian->status_bayar === 'tunai'
                ? 'Pembayaran tunai ke '.$pembelian->nama_supplier
                : 'Utang pembelian BBM ke '.$pembelian->nama_supplier,
        ]);
    }

    // Dipanggil saat sebuah payroll run dikirim (draft → dikirim).
    // Membuat SATU jurnal gabungan untuk seluruh petugas dalam run (bukan per
    // petugas), memakai TOTAL gaji bersih (sudah termasuk penyesuaian manual &
    // sudah di-floor minimum 0).
    //
    // Berbeda dari dariPenjualan/dariPengeluaran yang no-op diam kalau akun tidak
    // ketemu: di sini kita SENGAJA melempar exception kalau akun COA wajib tidak
    // ada, supaya DB::transaction di PayrollController::kirim() ikut rollback dan
    // payroll TIDAK jadi "dikirim tanpa jurnal". Idempotent: kalau jurnal untuk
    // run ini sudah ada, tidak membuat duplikat.
    public static function dariPayroll(PayrollRun $run): void
    {
        // Safety anti-duplikat: satu run = maksimal satu jurnal payroll.
        $sudahAda = JurnalUmum::where('sumber', 'payroll')
            ->where('referensi_id', $run->id)
            ->exists();
        if ($sudahAda) {
            return;
        }

        // Total yang dibayarkan = SUM total_gaji_bersih semua detail run ini.
        $total = (int) $run->details()->sum('total_gaji_bersih');

        // Tidak ada beban gaji untuk dicatat (mis. run tanpa petugas / semua 0):
        // lewati pembuatan jurnal, biarkan proses kirim tetap sukses.
        if ($total <= 0) {
            return;
        }

        // Akun Beban Gaji Karyawan (5103) & Kas (1101). Cari by kode_akun.
        $akunBebanGaji = Coa::where('kode_akun', '5103')->value('id');
        $akunKas = Coa::where('kode_akun', '1101')->value('id');

        if (! $akunBebanGaji || ! $akunKas) {
            throw new \RuntimeException(
                'Akun COA untuk penggajian tidak lengkap (butuh 5103 Beban Gaji & 1101 Kas). '
                .'Jurnal gaji tidak bisa dibuat — pengiriman payroll dibatalkan.'
            );
        }

        $tanggal = $run->dikirim_pada ?? now();

        $jurnal = JurnalUmum::create([
            'nomor_jurnal' => JurnalUmum::generateNomor($tanggal),
            'tanggal' => $tanggal,
            'keterangan' => 'Pembayaran Gaji Periode '
                .$run->periode_mulai->format('d/m/Y').' - '
                .$run->periode_selesai->format('d/m/Y'),
            'sumber' => 'payroll',
            'referensi_id' => $run->id,
            'dibuat_oleh' => auth()->id(),
        ]);

        // Debit: Beban Gaji Karyawan bertambah
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunBebanGaji,
            'posisi' => 'debit',
            'jumlah' => $total,
            'keterangan' => 'Beban gaji petugas periode ini',
        ]);

        // Kredit: Kas berkurang
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunKas,
            'posisi' => 'kredit',
            'jumlah' => $total,
            'keterangan' => 'Pembayaran gaji petugas periode ini',
        ]);
    }
}
