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

    // Dipanggil SEKALI saat setup awal pembukuan (fitur Saldo Awal): kas awal
    // + persediaan BBM awal per tangki digabung jadi SATU jurnal, dengan
    // baris debit dinamis (satu per tangki yang stok_awal & harga_awal-nya
    // terisi, kas awal ikut jadi baris debit kalau > 0) dan SATU baris
    // kredit Modal Pemilik senilai total semua debit di atas.
    //
    // $tangkiData: array of ['tangki' => TangkiBbm, 'stok_awal' => int, 'harga_awal' => int].
    // Baris tangki dilewati (tidak jadi baris jurnal) kalau stok_awal atau
    // harga_awal-nya 0/kosong, atau jenis BBM tangki itu belum punya akun
    // persediaan terdaftar (coa_persediaan_id) - konsisten dengan pola
    // silent-skip di dariPembelian()/dariPenjualan(), supaya satu tangki yang
    // belum lengkap datanya tidak memblokir baris tangki lain yang valid.
    public static function dariSaldoAwal(string $tanggal, int $kasAwal, array $tangkiData): JurnalUmum
    {
        $akunKas = Coa::where('kode_akun', '1101')->value('id');
        $akunModal = Coa::where('kode_akun', '3101')->value('id');

        if (! $akunKas || ! $akunModal) {
            throw new \RuntimeException(
                'Akun COA untuk saldo awal tidak lengkap (butuh 1101 Kas & 3101 Modal Pemilik). Saldo awal tidak bisa dicatat.'
            );
        }

        // Kumpulkan dulu baris-baris debit yang valid (termasuk hitung total)
        // SEBELUM membuat header jurnal - supaya kalau ternyata tidak ada
        // satupun nilai untuk dicatat, kita bisa gagal lebih awal tanpa
        // meninggalkan header jurnal kosong tak terpakai.
        $baris = [];

        if ($kasAwal > 0) {
            $baris[] = [
                'coa_id' => $akunKas,
                'jumlah' => $kasAwal,
                'keterangan' => 'Kas awal pembukuan',
            ];
        }

        foreach ($tangkiData as $row) {
            $stokAwal = (int) $row['stok_awal'];
            $hargaAwal = (int) $row['harga_awal'];

            if ($stokAwal <= 0 || $hargaAwal <= 0) {
                continue;
            }

            $tangki = $row['tangki'];
            $akunPersediaan = $tangki->masterBbm?->coa_persediaan_id;

            if (! $akunPersediaan) {
                continue;
            }

            $baris[] = [
                'coa_id' => $akunPersediaan,
                'jumlah' => $stokAwal * $hargaAwal,
                'keterangan' => 'Persediaan awal '.($tangki->masterBbm->jenis_bbm ?? '').' - '.$tangki->nama_tangki,
            ];
        }

        $totalDebit = array_sum(array_column($baris, 'jumlah'));

        if ($totalDebit <= 0) {
            throw new \RuntimeException('Tidak ada nilai saldo awal untuk dicatat (kas awal & seluruh persediaan tangki kosong).');
        }

        $jurnal = JurnalUmum::create([
            'nomor_jurnal' => JurnalUmum::generateNomor($tanggal),
            'tanggal' => $tanggal,
            'keterangan' => 'Saldo awal pembukuan (modal awal pemilik)',
            'sumber' => 'saldo_awal',
            'referensi_id' => null,
            'dibuat_oleh' => auth()->id(),
        ]);

        foreach ($baris as $b) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id' => $b['coa_id'],
                'posisi' => 'debit',
                'jumlah' => $b['jumlah'],
                'keterangan' => $b['keterangan'],
            ]);
        }

        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'coa_id' => $akunModal,
            'posisi' => 'kredit',
            'jumlah' => $totalDebit,
            'keterangan' => 'Modal awal pemilik',
        ]);

        return $jurnal;
    }

    // Dipanggil saat akun COA baru (kategori aset/kewajiban/modal) diberi Saldo
    // Awal statis lewat form Tambah Akun COA (lihat CoaController::store()).
    // BEDA dari dariSaldoAwal() (Kas+Persediaan, sumber 'saldo_awal', one-time
    // lock) - method ini pakai sumber 'saldo_awal_akun' SUPAYA proteksi
    // one-time-lock milik halaman Saldo Awal lama tidak ikut memblokir fitur
    // ini; boleh dipakai berkali-kali untuk akun berbeda-beda. Tanggal jurnal
    // selalu now() (tidak ada input tanggal manual, sesuai keputusan).
    public static function dariSaldoAwalAkun(Coa $akunBaru): JurnalUmum
    {
        $akunModal = Coa::where('kode_akun', '3101')->value('id');

        if (! $akunModal) {
            throw new \RuntimeException(
                'Akun COA 3101 Modal Pemilik tidak ditemukan. Saldo awal akun tidak bisa dicatat.'
            );
        }

        $jumlah = $akunBaru->saldo_awal;
        $tanggal = now();

        $jurnal = JurnalUmum::create([
            'nomor_jurnal' => JurnalUmum::generateNomor($tanggal),
            'tanggal' => $tanggal,
            'keterangan' => 'Saldo awal akun '.$akunBaru->nama_akun.' ('.$akunBaru->kode_akun.')',
            'sumber' => 'saldo_awal_akun',
            'referensi_id' => $akunBaru->id,
            'dibuat_oleh' => auth()->id(),
        ]);

        if ($akunBaru->kategori === 'aset') {
            // Aset bertambah: debit akun baru, kredit Modal Pemilik
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id' => $akunBaru->id,
                'posisi' => 'debit',
                'jumlah' => $jumlah,
                'keterangan' => 'Saldo awal '.$akunBaru->nama_akun,
            ]);

            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id' => $akunModal,
                'posisi' => 'kredit',
                'jumlah' => $jumlah,
                'keterangan' => 'Modal awal dari saldo akun '.$akunBaru->nama_akun,
            ]);
        } else {
            // kewajiban: kredit akun baru, debit Modal Pemilik.
            // modal: SECARA TEKNIS diperlakukan sama (kredit akun baru, debit
            // Modal Pemilik) - kasus akun modal baru yang punya saldo awal
            // sendiri (mis. setoran modal tambahan) jarang dipakai, tapi tetap
            // ditangani di sini supaya form tidak error kalau kategori ini dipilih.
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id' => $akunBaru->id,
                'posisi' => 'kredit',
                'jumlah' => $jumlah,
                'keterangan' => 'Saldo awal '.$akunBaru->nama_akun,
            ]);

            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'coa_id' => $akunModal,
                'posisi' => 'debit',
                'jumlah' => $jumlah,
                'keterangan' => 'Modal awal dari saldo akun '.$akunBaru->nama_akun,
            ]);
        }

        return $jurnal;
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
