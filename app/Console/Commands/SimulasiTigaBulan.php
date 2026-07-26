<?php

namespace App\Console\Commands;

use App\Models\Absensis;
use App\Models\Coa;
use App\Models\Lembur;
use App\Models\MasterBbm;
use App\Models\PembelianBbm;
use App\Models\Pengeluaran;
use App\Models\PenjualanBbm;
use App\Models\TangkiBbm;
use App\Models\User;
use App\Services\JurnalService;
use App\Services\PayrollService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Simulasi operasional SPBU 3 bulan (2026-04-26 s/d 2026-07-26), memakai
 * Eloquent model + service asli (JurnalService, PayrollService) — BUKAN insert
 * manual — supaya seluruh validasi, model event (hitung liter/subtotal/HPP,
 * decrement stok, moving average, cek stok cukup) & jurnal otomatis ikut jalan
 * persis seperti lewat controller.
 *
 * Titik mula = Saldo Awal (JRN per 2026-04-26) yang sudah diinput sebelumnya.
 */
class SimulasiTigaBulan extends Command
{
    protected $signature = 'seed:simulasi-tiga-bulan {--seed=20260426 : Seed RNG supaya hasil reproducible} {--fresh : Hapus dulu data transaksi dalam rentang (absensi/penjualan/pembelian/pengeluaran/payroll) sebelum generate}';

    protected $description = 'Simulasikan operasional SPBU 3 bulan (presensi, penjualan, pembelian, pengeluaran, payroll) lewat model+service asli';

    private Carbon $mulai;
    private Carbon $selesai;

    /** @var array<string,array{start:string,end:string}> */
    private array $shiftMeta = [
        'Pagi'  => ['start' => '07:00', 'end' => '15:00'],
        'Siang' => ['start' => '15:00', 'end' => '23:00'],
        'Malam' => ['start' => '23:00', 'end' => '07:00'],
    ];

    private array $petugas = [];      // shift => User (owner tetap)
    private ?User $rina = null;       // petugas rotasi/backup
    private ?User $manager = null;

    private array $master = [];       // jenis => MasterBbm
    private array $tangkiId = [];     // jenis => tangki_id
    private array $meter = [];        // jenis => meter berjalan (kontinu)
    private array $hppBeli = [];      // jenis => harga beli berjalan (random walk)

    // Batas harga beli per jenis (di bawah harga jual, margin sehat).
    private array $hppBatas = [
        'Pertalite' => [8900, 9600],
        'Pertamax'  => [15100, 15900],
        'Solar'     => [6300, 6800],
    ];

    // Volume dasar per shift (liter) sebelum faktor shift/akhir-pekan/noise.
    private array $volDasar = [
        'Pertalite' => 300,
        'Solar'     => 160,
        'Pertamax'  => 60,
    ];

    private array $shiftFaktor = ['Pagi' => 1.1, 'Siang' => 1.2, 'Malam' => 0.6];

    public function handle(): int
    {
        mt_srand((int) $this->option('seed'));

        $this->mulai   = Carbon::parse('2026-04-26')->startOfDay();
        $this->selesai = Carbon::parse('2026-07-26')->startOfDay();

        // ── Guard: butuh Saldo Awal, dan rentang harus kosong ─────────────
        $adaSaldoAwal = DB::table('jurnal_umum')->where('sumber', 'saldo_awal')->exists();
        if (! $adaSaldoAwal) {
            $this->error('Saldo Awal belum diinput (tidak ada jurnal sumber=saldo_awal). Input dulu Saldo Awal sebagai titik mula.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->hapusRentang();
        }

        $adaAbsensi = Absensis::whereBetween('tanggal', [$this->mulai->toDateString(), $this->selesai->toDateString()])->exists();
        if ($adaAbsensi) {
            $this->error('Sudah ada data absensi dalam rentang. Jalankan dengan --fresh untuk menghapus dulu, atau reset DB.');
            return self::FAILURE;
        }

        // ── Resolusi entitas ──────────────────────────────────────────────
        $this->manager = User::where('role', 'manager')->first();
        if (! $this->manager) {
            $this->error('User manager tidak ditemukan.');
            return self::FAILURE;
        }
        // Login supaya auth()->id() di service (dicatat_oleh/dibuat_oleh) terisi.
        Auth::login($this->manager);

        $budi = User::where('name', 'Budi Santoso')->first();
        $siti = User::where('name', 'Siti Aminah')->first();
        $agus = User::where('name', 'Agus Wijaya')->first();
        $this->rina = User::where('name', 'rina')->first();

        if (! $budi || ! $siti || ! $agus || ! $this->rina) {
            $this->error('Petugas tidak lengkap (butuh Budi/Siti/Agus/rina).');
            return self::FAILURE;
        }

        $this->petugas = ['Pagi' => $budi, 'Siang' => $siti, 'Malam' => $agus];

        // Backdate Agus & rina ke 2026-04-26 (keputusan user: keempat petugas
        // aktif sejak awal, 3 shift terisi penuh 3 bulan).
        foreach ([$agus, $this->rina] as $u) {
            if (! $u->tanggal_bergabung || $u->tanggal_bergabung->gt($this->mulai)) {
                $u->update(['tanggal_bergabung' => $this->mulai->toDateString()]);
                $this->line("  Backdate tanggal_bergabung {$u->name} -> {$this->mulai->toDateString()}");
            }
        }

        foreach (MasterBbm::where('is_aktif', 1)->get() as $m) {
            $this->master[$m->jenis_bbm] = $m;
        }
        foreach (TangkiBbm::with('masterBbm')->get() as $t) {
            $jenis = $t->masterBbm->jenis_bbm ?? null;
            if ($jenis) {
                $this->tangkiId[$jenis] = $t->id;
                $this->meter[$jenis]    = 100000; // titik awal meter dispenser (arbitrer, yang penting kontinu)
                $this->hppBeli[$jenis]  = (int) $t->harga_pokok_rata2 ?: $this->hppBatas[$jenis][0];
            }
        }

        $this->info('Mulai simulasi '.$this->mulai->toDateString().' s/d '.$this->selesai->toDateString());

        // ── FASE 1-3: presensi + penjualan + pembelian (per hari) ─────────
        $bulanBerjalan = null;
        $stat = ['absensi' => 0, 'penjualan' => 0, 'pembelian' => 0, 'lembur' => 0];

        for ($tgl = $this->mulai->copy(); $tgl->lte($this->selesai); $tgl->addDay()) {
            DB::transaction(function () use ($tgl, &$stat) {
                $this->prosesHari($tgl, $stat);
            });

            $labelBulan = $tgl->format('Y-m');
            if ($labelBulan !== $bulanBerjalan) {
                if ($bulanBerjalan !== null) {
                    $this->line("  [{$bulanBerjalan}] selesai — absensi:{$stat['absensi']} penjualan:{$stat['penjualan']} pembelian:{$stat['pembelian']} lembur:{$stat['lembur']}");
                }
                $bulanBerjalan = $labelBulan;
            }
        }
        $this->line("  [{$bulanBerjalan}] selesai — absensi:{$stat['absensi']} penjualan:{$stat['penjualan']} pembelian:{$stat['pembelian']} lembur:{$stat['lembur']}");

        // ── FASE 4: pengeluaran operasional bulanan ───────────────────────
        $this->prosesPengeluaran();

        // ── FASE 5: payroll 3 periode ─────────────────────────────────────
        $this->prosesPayroll();

        $this->info('Simulasi selesai.');
        $this->ringkasan();

        return self::SUCCESS;
    }

    // ── FASE 1-3 ──────────────────────────────────────────────────────────
    private function prosesHari(Carbon $tgl, array &$stat): void
    {
        $weekend = $tgl->isWeekend();

        // Status tiap owner shift
        $statusOwner = [];
        foreach (['Pagi', 'Siang', 'Malam'] as $shift) {
            $statusOwner[$shift] = $this->rollStatus(false);
        }

        // rina: rotasi harian; kalau ada owner absen, dia backup shift itu.
        $rinaStatus = $this->rollStatus(true);
        $shiftAbsen = [];
        foreach (['Pagi', 'Siang', 'Malam'] as $shift) {
            if (! in_array($statusOwner[$shift]['status'], ['hadir'], true)) {
                $shiftAbsen[] = $shift;
            }
        }
        $rotasiDefault = ['Pagi', 'Siang', 'Malam'][(int) abs($this->mulai->diffInDays($tgl)) % 3];
        $rinaShift = ($rinaStatus['status'] === 'hadir' && $shiftAbsen)
            ? $shiftAbsen[0]
            : $rotasiDefault;

        // Buat absensi + tentukan operator penyaji per shift.
        $absensiOwner = [];
        foreach (['Pagi', 'Siang', 'Malam'] as $shift) {
            $s = $statusOwner[$shift];
            if ($s['tercatat']) {
                $absensiOwner[$shift] = $this->buatAbsensi($this->petugas[$shift], $tgl, $shift, $s, $stat);
            }
        }

        $absensiRina = null;
        if ($rinaStatus['tercatat']) {
            $absensiRina = $this->buatAbsensi($this->rina, $tgl, $rinaShift, $rinaStatus, $stat);
        }

        // Coverage per shift + operator penyaji (untuk absensis_id penjualan).
        foreach (['Pagi', 'Siang', 'Malam'] as $shift) {
            $absensiPenyaji = null;

            if (($statusOwner[$shift]['status'] === 'hadir') && isset($absensiOwner[$shift])) {
                $absensiPenyaji = $absensiOwner[$shift];
            } elseif ($rinaShift === $shift && $rinaStatus['status'] === 'hadir' && $absensiRina) {
                $absensiPenyaji = $absensiRina; // rina meng-cover
            }

            if ($absensiPenyaji) {
                $this->jualShift($tgl, $shift, $absensiPenyaji->id, $weekend, $stat);
            }
        }
    }

    private function rollStatus(bool $rotasi): array
    {
        $r = mt_rand(1, 1000) / 1000;

        // rotasi (rina): tanpa alpha, lebih rajin.
        if ($rotasi) {
            if ($r <= 0.86) return ['status' => 'hadir', 'menit_telat' => 0, 'tercatat' => true];
            if ($r <= 0.91) return ['status' => 'hadir', 'menit_telat' => mt_rand(1, 10), 'tercatat' => true];
            if ($r <= 0.94) return ['status' => 'hadir', 'menit_telat' => mt_rand(11, 30), 'tercatat' => true];
            if ($r <= 0.97) return ['status' => 'izin',  'menit_telat' => 0, 'tercatat' => true];
            if ($r <= 0.992) return ['status' => 'sakit', 'menit_telat' => 0, 'tercatat' => true];
            return ['status' => 'skip', 'menit_telat' => 0, 'tercatat' => false]; // hari tidak tercatat (jarang)
        }

        if ($r <= 0.80)  return ['status' => 'hadir', 'menit_telat' => 0, 'tercatat' => true];
        if ($r <= 0.87)  return ['status' => 'hadir', 'menit_telat' => mt_rand(1, 10), 'tercatat' => true];   // dalam toleransi
        if ($r <= 0.91)  return ['status' => 'hadir', 'menit_telat' => mt_rand(11, 30), 'tercatat' => true];  // lewat toleransi
        if ($r <= 0.945) return ['status' => 'izin', 'menit_telat' => 0, 'tercatat' => true];
        if ($r <= 0.98)  return ['status' => 'sakit', 'menit_telat' => 0, 'tercatat' => true];
        if ($r <= 0.992) return ['status' => 'tidak_hadir', 'menit_telat' => 0, 'tercatat' => true];          // alpha
        return ['status' => 'skip', 'menit_telat' => 0, 'tercatat' => false];                                 // hari tidak tercatat
    }

    private function buatAbsensi(User $user, Carbon $tgl, string $shift, array $s, array &$stat): Absensis
    {
        $meta = $this->shiftMeta[$shift];
        $hadir = $s['status'] === 'hadir';

        $jamMasuk = null;
        $jamKeluar = null;
        if ($hadir) {
            $jamMasuk = Carbon::parse($tgl->toDateString().' '.$meta['start'])->addMinutes($s['menit_telat'])->format('H:i:s');
            $jamKeluar = $meta['end'].':00';
        }

        $absensi = Absensis::create([
            'user_id'      => $user->id,
            'tanggal'      => $tgl->toDateString(),
            'shift'        => $shift,
            'status_hadir' => $s['status'],
            'jam_masuk'    => $jamMasuk,
            'jam_keluar'   => $jamKeluar,
            'menit_telat'  => $hadir ? $s['menit_telat'] : 0,
            'keterangan'   => 'Simulasi 3 bulan',
            'dicatat_oleh' => $this->manager->id,
        ]);
        $stat['absensi']++;

        // Lembur approved sesekali untuk yang hadir.
        if ($hadir && (mt_rand(1, 100) <= 8)) {
            $jam = mt_rand(1, 3);
            $mulaiLembur = $meta['end'];
            $selesaiLembur = Carbon::parse($tgl->toDateString().' '.$meta['end'])->addHours($jam)->format('H:i');
            Lembur::create([
                'user_id'          => $user->id,
                'tanggal'          => $tgl->toDateString(),
                'jam_mulai'        => $mulaiLembur,
                'jam_selesai'      => $selesaiLembur,
                'alasan'           => 'Lembur operasional (simulasi)',
                'status'           => 'approved',
                'disetujui_oleh'   => $this->manager->id,
                'catatan_approval' => 'Auto-approved (simulasi)',
            ]);
            $stat['lembur']++;
        }

        return $absensi;
    }

    private function jualShift(Carbon $tgl, string $shift, int $absensiId, bool $weekend, array &$stat): void
    {
        // Jenis yang terjual di shift ini: Pertalite selalu, Solar sering, Pertamax kadang.
        $jenisTerjual = ['Pertalite'];
        if (mt_rand(1, 100) <= 85) $jenisTerjual[] = 'Solar';
        if (mt_rand(1, 100) <= 65) $jenisTerjual[] = 'Pertamax';

        foreach ($jenisTerjual as $jenis) {
            $volume = $this->hitungVolume($jenis, $shift, $weekend);
            if ($volume < 1) continue;

            $this->pastikanStok($jenis, $tgl, $volume, $stat);

            $master = $this->master[$jenis];
            $meterAwal  = $this->meter[$jenis];
            $meterAkhir = $meterAwal + $volume;
            $this->meter[$jenis] = $meterAkhir;

            $penjualan = PenjualanBbm::create([
                'tanggal'           => $tgl->toDateString(),
                'shift'             => $shift,
                'absensis_id'       => $absensiId,
                'pulau'             => '1',
                'jenis_bbm'         => $jenis,
                'ron'               => $master->ron,
                'coa_pendapatan_id' => $master->coa_pendapatan_id,
                'tangki_id'         => $this->tangkiId[$jenis],
                'meter_awal'        => $meterAwal,
                'meter_akhir'       => $meterAkhir,
                'harga_per_liter'   => $master->harga_per_liter,
                'catatan'           => 'Simulasi 3 bulan',
                'dicatat_oleh'      => $this->manager->id,
            ]);

            JurnalService::dariPenjualan($penjualan);
            $stat['penjualan']++;
        }
    }

    private function hitungVolume(string $jenis, string $shift, bool $weekend): int
    {
        $base = $this->volDasar[$jenis];
        $v = $base * $this->shiftFaktor[$shift];
        if ($weekend) $v *= 1.35;
        $v *= mt_rand(80, 120) / 100; // noise ±20%

        return (int) round($v);
    }

    private function pastikanStok(string $jenis, Carbon $tgl, int $volume, array &$stat): void
    {
        $tangki = TangkiBbm::find($this->tangkiId[$jenis]);
        $buffer = $this->volDasar[$jenis] * 3; // buffer ~beberapa shift ke depan

        if (($tangki->stok_liter - $volume) >= $buffer) {
            return; // stok masih aman
        }

        // Restock: top-up ke ~85% kapasitas, tidak melebihi kapasitas.
        $cap    = $tangki->kapasitas_liter;
        $target = (int) round($cap * 0.85);
        $vol    = max($target - $tangki->stok_liter, $volume + $buffer);
        if ($tangki->stok_liter + $vol > $cap) {
            $vol = $cap - $tangki->stok_liter;
        }
        if ($vol < 1) return; // sudah mepet kapasitas (jarang)

        // Harga beli random-walk, tetap dalam batas (di bawah harga jual).
        [$lo, $hi] = $this->hppBatas[$jenis];
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
            'tanggal'         => $tgl->toDateString(),
            'nama_supplier'   => $suppliers[array_rand($suppliers)],
            'tangki_id'       => $this->tangkiId[$jenis],
            'volume_liter'    => $vol,
            'harga_per_liter' => $hpp,
            'status_bayar'    => $status,
            'dicatat_oleh'    => $this->manager->id,
        ]);

        JurnalService::dariPembelian($pembelian);
        $stat['pembelian']++;
    }

    // ── FASE 4 ──────────────────────────────────────────────────────────────
    private function prosesPengeluaran(): void
    {
        $listrik = Coa::where('kode_akun', '5101-1')->value('id');
        $air     = Coa::where('kode_akun', '5101-2')->value('id');
        $atk     = Coa::where('kode_akun', '5102-1')->value('id');
        $rawat   = Coa::where('kode_akun', '5102-2')->value('id');

        // 3 bulan operasional penuh: Mei, Jun, Jul 2026 (tanggal 10 tiap bulan).
        $bulanList = ['2026-05-10', '2026-06-10', '2026-07-10'];

        foreach ($bulanList as $i => $tanggal) {
            DB::transaction(function () use ($tanggal, $listrik, $air, $atk, $rawat, $i) {
                if ($listrik) {
                    $this->buatPengeluaran($tanggal, $listrik, 'Tagihan listrik PLN bulanan', mt_rand(1800, 2400) * 1000);
                }
                if ($air) {
                    $this->buatPengeluaran($tanggal, $air, 'Tagihan air PDAM bulanan', mt_rand(300, 500) * 1000);
                }
                // ATK/Perawatan tidak tiap bulan (sesekali).
                if ($atk && $i === 0) {
                    $this->buatPengeluaran($tanggal, $atk, 'Pembelian ATK kantor', mt_rand(150, 350) * 1000);
                }
                if ($rawat && $i === 1) {
                    $this->buatPengeluaran($tanggal, $rawat, 'Servis dispenser & perawatan pompa', mt_rand(800, 1500) * 1000);
                }
            });
        }
    }

    private function buatPengeluaran(string $tanggal, int $coaId, string $keterangan, int $jumlah): void
    {
        $pengeluaran = Pengeluaran::create([
            'tanggal'      => $tanggal,
            'coa_id'       => $coaId,
            'keterangan'   => $keterangan,
            'jumlah'       => $jumlah,
            'dicatat_oleh' => $this->manager->id,
        ]);

        JurnalService::dariPengeluaran($pengeluaran);
    }

    // ── FASE 5 ──────────────────────────────────────────────────────────────
    private function prosesPayroll(): void
    {
        $payroll = app(PayrollService::class);

        $periode = [
            ['2026-04-26', '2026-05-25'],
            ['2026-05-26', '2026-06-25'],
            ['2026-06-26', '2026-07-25'],
        ];

        foreach ($periode as [$mulai, $selesai]) {
            $run = $payroll->generateDraft(
                Carbon::parse($mulai),
                Carbon::parse($selesai),
                $this->manager->id
            );

            // "Kirim" (sama seperti PayrollController::kirim), tapi dikirim_pada
            // di-set ke tanggal gajian periode (bukan now()) supaya beban gaji
            // jatuh di bulan yang benar untuk Laporan Laba Rugi historis.
            DB::transaction(function () use ($run, $selesai) {
                $run->update([
                    'status'       => 'dikirim',
                    'dikirim_oleh' => $this->manager->id,
                    'dikirim_pada' => Carbon::parse($selesai)->endOfDay(),
                ]);
                JurnalService::dariPayroll($run->fresh());
            });

            $this->line("  Payroll {$mulai} s/d {$selesai}: dikirim (details: {$run->details()->count()})");
        }
    }

    // ── Hapus rentang (opsi --fresh) ────────────────────────────────────────
    private function hapusRentang(): void
    {
        DB::transaction(function () {
            $dari = $this->mulai->toDateString();
            $sampai = $this->selesai->toDateString();

            // Jurnal non-saldo_awal dalam rentang (hapus detail dulu).
            $jurnalIds = DB::table('jurnal_umum')
                ->where('sumber', '!=', 'saldo_awal')
                ->pluck('id');
            DB::table('jurnal_detail')->whereIn('jurnal_id', $jurnalIds)->delete();
            DB::table('jurnal_umum')->where('sumber', '!=', 'saldo_awal')->delete();

            DB::table('penjualan_bbm')->whereBetween('tanggal', [$dari, $sampai])->delete();
            DB::table('pembelian_bbm')->whereBetween('tanggal', [$dari, $sampai])->delete();
            DB::table('absensis')->whereBetween('tanggal', [$dari, $sampai])->delete();
            DB::table('lembur')->whereBetween('tanggal', [$dari, $sampai])->delete();
            DB::table('pengeluarans')->whereBetween('tanggal', [$dari, $sampai])->delete();
            DB::table('payroll_penyesuaian')->delete();
            DB::table('payroll_details')->delete();
            DB::table('payroll_runs')->delete();
        });
        $this->warn('Data transaksi dalam rentang dihapus (--fresh).');
    }

    // ── Ringkasan singkat (verifikasi detail dilakukan terpisah) ────────────
    private function ringkasan(): void
    {
        $this->newLine();
        $this->info('=== Ringkasan baris dibuat ===');
        $this->table(['Tabel', 'Jumlah'], [
            ['absensis', Absensis::count()],
            ['penjualan_bbm', PenjualanBbm::count()],
            ['pembelian_bbm', PembelianBbm::count()],
            ['pengeluarans', Pengeluaran::count()],
            ['lembur', Lembur::count()],
            ['payroll_runs', DB::table('payroll_runs')->count()],
            ['jurnal_umum', DB::table('jurnal_umum')->count()],
        ]);
    }
}
