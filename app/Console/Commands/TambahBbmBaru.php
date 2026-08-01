<?php

namespace App\Console\Commands;

use App\Models\Absensis;
use App\Models\Coa;
use App\Models\MasterBbm;
use App\Models\PembelianBbm;
use App\Models\PenjualanBbm;
use App\Models\TangkiBbm;
use App\Models\User;
use App\Services\JurnalService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Tambah 2 jenis BBM baru (Pertamax Green 95 RON95, Pertamax Turbo RON98)
 * lengkap dengan master data (master_bbm, COA persediaan+pendapatan, tangki)
 * dan histori transaksi (pembelian+penjualan) sejak 2026-04-26 s/d tanggal
 * terakhir data absensi 'hadir' yang tersedia - supaya kedua produk ini
 * terlihat sudah beroperasi sejak awal periode yang sama dengan
 * SimulasiTigaBulan.
 *
 * SENGAJA dipisah dari SimulasiTigaBulan.php (bukan reuse/modifikasi
 * langsung) supaya tidak berisiko mengganggu data Pertalite/Pertamax/Solar
 * yang sudah ada - command ini HANYA membuat/menyentuh baris untuk 2 jenis
 * BBM baru ini.
 *
 * Reuse absensi yang SUDAH ADA (status_hadir='hadir') sebagai penentu shift
 * mana yang "buka" untuk dijual - TIDAK PERNAH membuat baris absensis baru.
 */
class TambahBbmBaru extends Command
{
    protected $signature = 'seed:tambah-bbm-baru
        {--seed=20260426 : Seed RNG supaya hasil reproducible}
        {--fresh : Hapus dulu histori pembelian/penjualan 2 jenis BBM baru ini (kalau sudah pernah dijalankan) sebelum generate ulang - master data & data BBM lama TIDAK ikut terhapus}';

    protected $description = 'Tambah 2 jenis BBM baru (Pertamax Green 95, Pertamax Turbo) + histori transaksi, tanpa menyentuh data BBM lama';

    // Konfigurasi per jenis BBM baru. Harga & margin beli disusun naik
    // bertahap dari Pertamax (RON92, Rp16.000/L) sesuai keputusan: RON lebih
    // tinggi -> harga jual lebih mahal. Volume dasar & peluang terjual
    // sengaja jauh lebih kecil dari 3 BBM lama (Pertalite 300L/shift dasar,
    // Solar 160L, Pertamax 60L) - produk baru, belum sepopuler.
    private const JENIS_BARU = [
        'Pertamax Green 95' => [
            'ron' => '95',
            'harga_jual' => 17000,
            'kode_persediaan' => '1104-4',
            'kode_pendapatan' => '4101-4',
            'volume_dasar' => 20,
            'chance_terjual' => 25, // persen peluang laku per shift
            'hpp_batas' => [15800, 16600],
        ],
        'Pertamax Turbo' => [
            'ron' => '98',
            'harga_jual' => 18500,
            'kode_persediaan' => '1104-5',
            'kode_pendapatan' => '4101-5',
            'volume_dasar' => 12,
            'chance_terjual' => 15,
            'hpp_batas' => [17200, 18000],
        ],
    ];

    private array $shiftFaktor = ['Pagi' => 1.1, 'Siang' => 1.2, 'Malam' => 0.6];

    private ?User $manager = null;
    private array $master = [];    // jenis => MasterBbm
    private array $tangkiId = [];  // jenis => tangki id
    private array $meter = [];     // jenis => meter berjalan (kontinu)
    private array $hppBeli = [];   // jenis => harga beli berjalan (random walk)

    public function handle(): int
    {
        mt_srand((int) $this->option('seed'));

        $this->manager = User::where('role', 'manager')->first();
        if (! $this->manager) {
            $this->error('User manager tidak ditemukan.');

            return self::FAILURE;
        }
        // Login supaya auth()->id() di JurnalService (dicatat_oleh/dibuat_oleh) terisi.
        Auth::login($this->manager);

        $jenisList = array_keys(self::JENIS_BARU);

        $sudahAda = PenjualanBbm::whereIn('jenis_bbm', $jenisList)->exists();
        if ($sudahAda) {
            if (! $this->option('fresh')) {
                $this->error('Histori 2 jenis BBM baru ini sudah pernah dibuat. Pakai --fresh untuk hapus & generate ulang (data BBM lama TIDAK ikut kehapus).');

                return self::FAILURE;
            }
            $this->hapusHistoriLama($jenisList);
        }

        // ── FASE 0: master data (idempotent) ─────────────────────────────
        $this->siapkanMasterData();

        // ── FASE 1: simulasikan pembelian+penjualan per hari ──────────────
        $dari = Carbon::parse('2026-04-26')->startOfDay();
        $tanggalAbsensiTerakhir = Absensis::where('status_hadir', 'hadir')->max('tanggal');
        $sampai = $tanggalAbsensiTerakhir ? Carbon::parse($tanggalAbsensiTerakhir)->startOfDay() : $dari->copy();

        $this->info("Rentang simulasi: {$dari->toDateString()} s/d {$sampai->toDateString()} (dibatasi tanggal terakhir absensi 'hadir' yang tersedia - tidak membuat absensi baru).");

        // Ambil semua shift 'hadir' dalam rentang, dikelompokkan per
        // (tanggal, shift) - kalau ada 2 absensi hadir bertumpuk di shift
        // yang sama (owner + rina backup rotasi yang kebetulan sama-sama
        // hadir), cuma satu yang dipakai untuk penjualan produk baru ini.
        $absensiPerTanggal = Absensis::where('status_hadir', 'hadir')
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('tanggal')->orderBy('shift')->orderBy('id')
            ->get()
            ->groupBy(fn ($a) => $a->tanggal->toDateString().'|'.$a->shift)
            ->map(fn ($group) => $group->first())
            ->groupBy(fn ($a) => $a->tanggal->toDateString());

        $stat = ['pembelian' => 0, 'penjualan' => 0];

        for ($tgl = $dari->copy(); $tgl->lte($sampai); $tgl->addDay()) {
            $absensiHariIni = $absensiPerTanggal->get($tgl->toDateString());
            if (! $absensiHariIni || $absensiHariIni->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($tgl, $absensiHariIni, &$stat) {
                foreach ($absensiHariIni as $absensi) {
                    $this->jualShift($tgl, $absensi, $stat);
                }
            });
        }

        $this->info('Selesai.');
        $this->table(['Tabel', 'Jumlah baris baru'], [
            ['pembelian_bbm', $stat['pembelian']],
            ['penjualan_bbm', $stat['penjualan']],
        ]);

        foreach ($this->tangkiId as $jenis => $id) {
            $t = TangkiBbm::find($id);
            $this->line("  Tangki {$jenis}: stok_liter={$t->stok_liter} harga_pokok_rata2={$t->harga_pokok_rata2}");
        }

        return self::SUCCESS;
    }

    // ── FASE 0 ────────────────────────────────────────────────────────────
    private function siapkanMasterData(): void
    {
        $parentPersediaan = Coa::where('kode_akun', '1104')->firstOrFail();
        $parentPendapatan = Coa::where('kode_akun', '4101')->firstOrFail();

        foreach (self::JENIS_BARU as $jenis => $cfg) {
            $coaPersediaan = Coa::firstOrCreate(
                ['kode_akun' => $cfg['kode_persediaan']],
                [
                    'parent_id' => $parentPersediaan->id,
                    'nama_akun' => 'Persediaan '.$jenis,
                    'kategori' => 'aset',
                    'posisi_normal' => 'debit',
                    'deskripsi' => 'Stok '.$jenis,
                    'is_aktif' => 1,
                ]
            );

            $coaPendapatan = Coa::firstOrCreate(
                ['kode_akun' => $cfg['kode_pendapatan']],
                [
                    'parent_id' => $parentPendapatan->id,
                    'nama_akun' => 'Pendapatan '.$jenis,
                    'kategori' => 'pendapatan',
                    'posisi_normal' => 'kredit',
                    'deskripsi' => 'Pendapatan penjualan '.$jenis,
                    'is_aktif' => 1,
                ]
            );

            $masterBbm = MasterBbm::firstOrCreate(
                ['jenis_bbm' => $jenis],
                [
                    'ron' => $cfg['ron'],
                    'harga_per_liter' => $cfg['harga_jual'],
                    'coa_pendapatan_id' => $coaPendapatan->id,
                    'coa_persediaan_id' => $coaPersediaan->id,
                    'is_aktif' => 1,
                ]
            );

            // Jaga-jaga kalau master_bbm sudah ada dari percobaan sebelumnya
            // tapi link COA-nya belum konsisten.
            if ($masterBbm->coa_persediaan_id !== $coaPersediaan->id || $masterBbm->coa_pendapatan_id !== $coaPendapatan->id) {
                $masterBbm->update([
                    'coa_persediaan_id' => $coaPersediaan->id,
                    'coa_pendapatan_id' => $coaPendapatan->id,
                ]);
            }

            $tangki = TangkiBbm::firstOrCreate(
                ['master_bbm_id' => $masterBbm->id],
                [
                    'nama_tangki' => 'Tangki '.$jenis,
                    'kapasitas_liter' => 10000,
                    'stok_liter' => 0,
                    'harga_pokok_rata2' => 0,
                    'is_aktif' => 1,
                ]
            );

            $this->master[$jenis] = $masterBbm;
            $this->tangkiId[$jenis] = $tangki->id;
            $this->meter[$jenis] = 100000; // titik awal meter dispenser (arbitrer, yang penting kontinu)
            $this->hppBeli[$jenis] = (int) $tangki->harga_pokok_rata2 ?: $cfg['hpp_batas'][0];

            $this->line("  Master data siap: {$masterBbm->jenis_bbm} (COA {$coaPersediaan->kode_akun}/{$coaPendapatan->kode_akun}, tangki id={$tangki->id})");
        }
    }

    // ── FASE 1 ────────────────────────────────────────────────────────────
    private function jualShift(Carbon $tgl, Absensis $absensi, array &$stat): void
    {
        $weekend = $tgl->isWeekend();
        $shift = $absensi->shift;

        foreach (self::JENIS_BARU as $jenis => $cfg) {
            if (mt_rand(1, 100) > $cfg['chance_terjual']) {
                continue;
            }

            $volume = $this->hitungVolume($jenis, $shift, $weekend);
            if ($volume < 1) {
                continue;
            }

            $this->pastikanStok($jenis, $tgl, $volume, $stat);

            $master = $this->master[$jenis];
            $meterAwal = $this->meter[$jenis];
            $meterAkhir = $meterAwal + $volume;
            $this->meter[$jenis] = $meterAkhir;

            $penjualan = PenjualanBbm::create([
                'tanggal' => $tgl->toDateString(),
                'shift' => $shift,
                'absensis_id' => $absensi->id,
                'pulau' => '1',
                'jenis_bbm' => $jenis,
                'ron' => $master->ron,
                'coa_pendapatan_id' => $master->coa_pendapatan_id,
                'tangki_id' => $this->tangkiId[$jenis],
                'meter_awal' => $meterAwal,
                'meter_akhir' => $meterAkhir,
                'harga_per_liter' => $master->harga_per_liter,
                'catatan' => 'Histori BBM baru (auto-generate)',
                'dicatat_oleh' => $this->manager->id,
            ]);

            JurnalService::dariPenjualan($penjualan);
            $stat['penjualan']++;
        }
    }

    private function hitungVolume(string $jenis, string $shift, bool $weekend): int
    {
        $base = self::JENIS_BARU[$jenis]['volume_dasar'];
        $v = $base * $this->shiftFaktor[$shift];
        if ($weekend) {
            $v *= 1.35;
        }
        $v *= mt_rand(80, 120) / 100; // noise ±20%

        return (int) round($v);
    }

    private function pastikanStok(string $jenis, Carbon $tgl, int $volume, array &$stat): void
    {
        $cfg = self::JENIS_BARU[$jenis];
        $tangki = TangkiBbm::find($this->tangkiId[$jenis]);
        $buffer = $cfg['volume_dasar'] * 3;

        if (($tangki->stok_liter - $volume) >= $buffer) {
            return; // stok masih aman
        }

        // Restock: top-up ke ~85% kapasitas, tidak melebihi kapasitas.
        // Stok awal 0 -> kondisi ini otomatis kena di shift pertama yang
        // mencoba jual, jadi baris pembelian pertama inilah yang jadi
        // "stok awal" produk ini (bukan jurnal saldo_awal_akun terpisah,
        // sesuai keputusan: 2 tangki baru mulai dari nol, terisi murni
        // lewat pembelian).
        $cap = $tangki->kapasitas_liter;
        $target = (int) round($cap * 0.85);
        $vol = max($target - $tangki->stok_liter, $volume + $buffer);
        if ($tangki->stok_liter + $vol > $cap) {
            $vol = $cap - $tangki->stok_liter;
        }
        if ($vol < 1) {
            return; // sudah mepet kapasitas (jarang)
        }

        // Harga beli random-walk, tetap dalam batas (di bawah harga jual).
        [$lo, $hi] = $cfg['hpp_batas'];
        $hpp = $this->hppBeli[$jenis] + mt_rand(-150, 150);
        $hpp = max($lo, min($hi, $hpp));
        $this->hppBeli[$jenis] = $hpp;

        $suppliers = [
            'PT Pertamina Patra Niaga',
            'PT Pertamina Patra Niaga - TBBM',
            'PT Pertamina Patra Niaga (Depo A)',
        ];
        $status = mt_rand(1, 100) <= 30 ? 'kredit' : 'tunai';

        $pembelian = PembelianBbm::create([
            'tanggal' => $tgl->toDateString(),
            'nama_supplier' => $suppliers[array_rand($suppliers)],
            'tangki_id' => $this->tangkiId[$jenis],
            'volume_liter' => $vol,
            'harga_per_liter' => $hpp,
            'status_bayar' => $status,
            'dicatat_oleh' => $this->manager->id,
        ]);

        JurnalService::dariPembelian($pembelian);
        $stat['pembelian']++;
    }

    // ── --fresh: hapus histori transaksi 2 jenis BBM baru ini SAJA ────────
    private function hapusHistoriLama(array $jenisList): void
    {
        DB::transaction(function () use ($jenisList) {
            $penjualanIds = PenjualanBbm::whereIn('jenis_bbm', $jenisList)->pluck('id');
            $jurnalPenjualanIds = DB::table('jurnal_umum')
                ->where('sumber', 'penjualan_bbm')
                ->whereIn('referensi_id', $penjualanIds)
                ->pluck('id');
            DB::table('jurnal_detail')->whereIn('jurnal_id', $jurnalPenjualanIds)->delete();
            DB::table('jurnal_umum')->whereIn('id', $jurnalPenjualanIds)->delete();
            PenjualanBbm::whereIn('jenis_bbm', $jenisList)->delete();

            $tangkiIds = TangkiBbm::whereHas('masterBbm', fn ($q) => $q->whereIn('jenis_bbm', $jenisList))->pluck('id');
            $pembelianIds = PembelianBbm::whereIn('tangki_id', $tangkiIds)->pluck('id');
            $jurnalPembelianIds = DB::table('jurnal_umum')
                ->where('sumber', 'pembelian_bbm')
                ->whereIn('referensi_id', $pembelianIds)
                ->pluck('id');
            DB::table('jurnal_detail')->whereIn('jurnal_id', $jurnalPembelianIds)->delete();
            DB::table('jurnal_umum')->whereIn('id', $jurnalPembelianIds)->delete();
            PembelianBbm::whereIn('id', $pembelianIds)->delete();

            TangkiBbm::whereIn('id', $tangkiIds)->update(['stok_liter' => 0, 'harga_pokok_rata2' => 0]);
        });

        $this->warn('Histori pembelian/penjualan 2 jenis BBM baru dihapus (--fresh). Master data (COA/master_bbm/tangki) dipertahankan.');
    }
}
