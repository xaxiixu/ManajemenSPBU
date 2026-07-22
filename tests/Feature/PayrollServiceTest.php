<?php

namespace Tests\Feature;

use App\Models\Absensis;
use App\Models\Lembur;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    private function setting(): PayrollSetting
    {
        return PayrollSetting::create([
            'tanggal_gajian'                => 25,
            'rate_lembur_per_jam'           => 20000,
            'rate_potongan_telat_per_menit' => 1000,
            'toleransi_telat_menit'         => 10,
            'kuota_izin_sakit_per_bulan'    => 2,
        ]);
    }

    private function petugas(array $overrides = []): User
    {
        return User::create(array_merge([
            'name'              => 'Petugas Uji',
            'email'             => 'petugas'.uniqid().'@test.com',
            'password'          => 'password',
            'role'              => 'petugas',
            'is_active'         => 1,
            'gaji_pokok'        => 3000000,
            'tanggal_bergabung' => '2020-01-01',
        ], $overrides));
    }

    public function test_periode_saat_ini_sebelum_tanggal_gajian(): void
    {
        $svc = new PayrollService();
        // Acuan 22 Jul, gajian 25 → periode 26 Jun s/d 25 Jul
        $p = $svc->periodeSaatIni($this->setting(), Carbon::create(2026, 7, 22));

        $this->assertSame('2026-06-26', $p['mulai']->toDateString());
        $this->assertSame('2026-07-25', $p['selesai']->toDateString());
    }

    public function test_periode_saat_ini_setelah_tanggal_gajian_pindah_ke_bulan_depan(): void
    {
        $svc = new PayrollService();
        // Acuan 26 Jul (sudah lewat gajian 25) → periode 26 Jul s/d 25 Agu
        $p = $svc->periodeSaatIni($this->setting(), Carbon::create(2026, 7, 26));

        $this->assertSame('2026-07-26', $p['mulai']->toDateString());
        $this->assertSame('2026-08-25', $p['selesai']->toDateString());
    }

    public function test_hitung_skenario_lengkap(): void
    {
        $setting = $this->setting();
        $petugas = $this->petugas();
        $svc     = new PayrollService();

        $mulai   = Carbon::create(2026, 6, 26);
        $selesai = Carbon::create(2026, 7, 25);
        // 30 hari → rate harian = 3.000.000 / 30 = 100.000

        // 1 hadir telat 25 menit (kena potong 15 menit × 1000 = 15.000)
        Absensis::create([
            'user_id' => $petugas->id, 'tanggal' => '2026-07-01', 'shift' => 'Pagi',
            'status_hadir' => 'hadir', 'menit_telat' => 25,
        ]);
        // 1 alpha → potong penuh 100.000
        Absensis::create([
            'user_id' => $petugas->id, 'tanggal' => '2026-07-02', 'shift' => 'Pagi',
            'status_hadir' => 'tidak_hadir',
        ]);
        // 2 sakit + 1 izin = 3 kejadian, kuota 2 → 1 kelebihan → 0.5 × 100.000 = 50.000
        Absensis::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-03', 'shift' => 'Pagi', 'status_hadir' => 'sakit']);
        Absensis::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-04', 'shift' => 'Pagi', 'status_hadir' => 'sakit']);
        Absensis::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-05', 'shift' => 'Pagi', 'status_hadir' => 'izin']);

        // Lembur approved 2 jam + 3 jam = 5 jam × 20.000 = 100.000
        Lembur::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-06', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'alasan' => 'x', 'status' => 'approved']);
        Lembur::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-07', 'jam_mulai' => '13:00', 'jam_selesai' => '16:00', 'alasan' => 'x', 'status' => 'approved']);
        // Lembur pending TIDAK dihitung
        Lembur::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-08', 'jam_mulai' => '13:00', 'jam_selesai' => '16:00', 'alasan' => 'x', 'status' => 'pending']);

        // hariIni sengaja di-set SEBELUM periode → tidak ada "hari berjalan", jadi
        // deteksi hari-tidak-tercatat non-aktif dan test ini murni menguji komponen
        // telat/alpha/sakit-izin/lembur. Fitur hari-tidak-tercatat diuji terpisah
        // di test_hari_tidak_tercatat_*.
        $h = $svc->hitung($petugas, $mulai, $selesai, $setting, $mulai->copy()->subDay());

        $this->assertSame(3000000, $h['gaji_pokok_prorate']);
        $this->assertSame(1, $h['jumlah_hadir']);
        $this->assertSame(1, $h['jumlah_kali_telat']);
        $this->assertSame(15, $h['total_menit_telat_kena_potong']);
        $this->assertSame(15000, $h['potongan_telat']);
        $this->assertSame(1, $h['jumlah_alpha']);
        $this->assertSame(2, $h['jumlah_sakit']);
        $this->assertSame(1, $h['jumlah_izin']);
        $this->assertSame(0, $h['jumlah_tidak_tercatat']);
        $this->assertSame(150000, $h['potongan_absen']); // 100.000 alpha + 50.000 kelebihan kuota
        $this->assertSame(5, $h['jumlah_jam_lembur']);
        $this->assertSame(100000, $h['uang_lembur']);
        // 3.000.000 − 15.000 − 150.000 + 100.000 = 2.935.000
        $this->assertSame(2935000, $h['total_gaji_bersih']);
    }

    public function test_prorate_petugas_baru(): void
    {
        $setting = $this->setting();
        // Bergabung 11 Jul (di dalam periode 26 Jun–25 Jul)
        $petugas = $this->petugas(['tanggal_bergabung' => '2026-07-11']);
        $svc     = new PayrollService();

        // Isolasi hari-tidak-tercatat (hariIni sebelum periode) — fokus prorate.
        $h = $svc->hitung($petugas, Carbon::create(2026, 6, 26), Carbon::create(2026, 7, 25), $setting, Carbon::create(2026, 6, 25));

        // 11 Jul s/d 25 Jul = 15 hari × 100.000 = 1.500.000
        $this->assertSame(1500000, $h['gaji_pokok_prorate']);
        $this->assertSame(0, $h['jumlah_tidak_tercatat']);
        $this->assertSame(1500000, $h['total_gaji_bersih']);
    }

    public function test_telat_dalam_toleransi_tidak_dipotong(): void
    {
        $setting = $this->setting();
        $petugas = $this->petugas();
        $svc     = new PayrollService();

        Absensis::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-01', 'shift' => 'Pagi', 'status_hadir' => 'hadir', 'menit_telat' => 10]);

        // Isolasi hari-tidak-tercatat (hariIni sebelum periode) — fokus toleransi telat.
        $h = $svc->hitung($petugas, Carbon::create(2026, 6, 26), Carbon::create(2026, 7, 25), $setting, Carbon::create(2026, 6, 25));

        $this->assertSame(0, $h['jumlah_kali_telat']);
        $this->assertSame(0, $h['potongan_telat']);
        $this->assertSame(3000000, $h['total_gaji_bersih']);
    }

    public function test_gaji_bersih_tidak_pernah_negatif(): void
    {
        $setting = $this->setting();
        $petugas = $this->petugas(['gaji_pokok' => 300000]); // kecil → rate harian 10.000
        $svc     = new PayrollService();

        // 30 alpha → potong 30 × 10.000 = 300.000 (habis), harusnya bersih 0 bukan minus
        for ($d = 1; $d <= 30; $d++) {
            $tgl = Carbon::create(2026, 6, 26)->addDays($d - 1)->toDateString();
            Absensis::create(['user_id' => $petugas->id, 'tanggal' => $tgl, 'shift' => 'Pagi', 'status_hadir' => 'tidak_hadir']);
        }

        // Isolasi hari-tidak-tercatat (semua 30 hari sudah terisi alpha, jadi tidak
        // ada yang kosong; hariIni di-set deterministik agar tidak tergantung jam nyata).
        $h = $svc->hitung($petugas, Carbon::create(2026, 6, 26), Carbon::create(2026, 7, 25), $setting, Carbon::create(2026, 6, 25));

        $this->assertSame(30, $h['jumlah_alpha']);
        $this->assertSame(0, $h['jumlah_tidak_tercatat']);
        $this->assertSame(0, $h['total_gaji_bersih']);
    }

    public function test_hari_tidak_tercatat_default_dipotong_setara_alpha(): void
    {
        $setting = $this->setting();
        $petugas = $this->petugas(); // gaji 3.000.000, rate harian 100.000
        $svc     = new PayrollService();

        $mulai   = Carbon::create(2026, 6, 26);
        $selesai = Carbon::create(2026, 7, 25);         // 30 hari
        $hariIni = Carbon::create(2026, 7, 25);         // seluruh periode sudah berjalan

        // Hanya 2 tanggal yang punya record (1 hadir, 1 alpha) → 28 tanggal kosong.
        Absensis::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-01', 'shift' => 'Pagi', 'status_hadir' => 'hadir']);
        Absensis::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-02', 'shift' => 'Pagi', 'status_hadir' => 'tidak_hadir']);

        $h = $svc->hitung($petugas, $mulai, $selesai, $setting, $hariIni);

        $this->assertSame(1, $h['jumlah_alpha']);
        $this->assertSame(28, $h['jumlah_tidak_tercatat']);          // 30 − 2 tanggal terisi
        // potongan_absen = alpha 100.000 + tidak-tercatat 28×100.000 = 2.900.000
        $this->assertSame(2900000, $h['potongan_absen']);
        // 3.000.000 − 2.900.000 = 100.000
        $this->assertSame(100000, $h['total_gaji_bersih']);
    }

    public function test_hari_tidak_tercatat_tidak_menghukum_tanggal_masa_depan(): void
    {
        $setting = $this->setting();
        $petugas = $this->petugas();
        $svc     = new PayrollService();

        $mulai   = Carbon::create(2026, 6, 26);
        $selesai = Carbon::create(2026, 7, 25);
        $hariIni = Carbon::create(2026, 7, 10);   // periode berjalan; 11–25 Jul belum terjadi

        // 1 record pada 10 Jul. Batas hitung = 26 Jun s/d 10 Jul = 15 hari.
        Absensis::create(['user_id' => $petugas->id, 'tanggal' => '2026-07-10', 'shift' => 'Pagi', 'status_hadir' => 'hadir']);

        $h = $svc->hitung($petugas, $mulai, $selesai, $setting, $hariIni);

        // 15 hari harus ada − 1 tercatat = 14 (BUKAN 29; tanggal 11–25 Jul tidak dihukum).
        $this->assertSame(14, $h['jumlah_tidak_tercatat']);
    }

    public function test_hari_tidak_tercatat_tidak_menghukum_sebelum_bergabung(): void
    {
        $setting = $this->setting();
        // Bergabung 11 Jul → hari 26 Jun–10 Jul bukan tanggung jawabnya.
        $petugas = $this->petugas(['tanggal_bergabung' => '2026-07-11']);
        $svc     = new PayrollService();

        $mulai   = Carbon::create(2026, 6, 26);
        $selesai = Carbon::create(2026, 7, 25);
        $hariIni = Carbon::create(2026, 7, 25);

        // Tidak ada record sama sekali. Batas = 11 Jul s/d 25 Jul = 15 hari.
        $h = $svc->hitung($petugas, $mulai, $selesai, $setting, $hariIni);

        $this->assertSame(1500000, $h['gaji_pokok_prorate']);        // prorate 15 hari
        $this->assertSame(15, $h['jumlah_tidak_tercatat']);          // BUKAN 30
        // potongan = 15 × 100.000 = 1.500.000 → habis, gaji bersih 0 (floor).
        $this->assertSame(0, $h['total_gaji_bersih']);
    }
}
