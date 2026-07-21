<?php

namespace Database\Seeders;

use App\Models\Absensis;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Database\Seeder;

class PayrollDemoSeeder extends Seeder
{
    /**
     * Data absensi + lembur untuk periode 26 Mei 2026 - 25 Jun 2026 (gajian
     * tanggal 25), dirancang supaya SEMUA aturan bisnis payroll bisa langsung
     * dites tanpa input manual satu-satu. Jalankan terpisah (bukan lewat
     * DatabaseSeeder default):
     *
     *   php artisan db:seed --class=PayrollDemoSeeder
     *
     * Butuh 3 akun petugas dari PetugasSeeder (budi/siti/agus.petugas@spbu.test)
     * sudah ada. Idempoten: aman dijalankan berkali-kali (updateOrCreate).
     *
     * CATATAN: tanggal_bergabung Agus diubah ke 2026-06-08 (masuk DI DALAM
     * periode ini) supaya periode ini punya kasus prorate. Ini menimpa nilai
     * 2026-07-10 dari PetugasSeeder yang dipakai untuk demo prorate periode
     * 26 Jun-25 Jul - kalau kamu masih mau menguji periode itu juga, set
     * ulang tanggal_bergabung Agus lewat Manajemen User sebelum generate.
     */
    public function run(): void
    {
        $budi = User::where('email', 'budi.petugas@spbu.test')->first();
        $siti = User::where('email', 'siti.petugas@spbu.test')->first();
        $agus = User::where('email', 'agus.petugas@spbu.test')->first();

        if (!$budi || !$siti || !$agus) {
            $this->command->error('Akun petugas belum ada. Jalankan dulu: php artisan db:seed --class=PetugasSeeder');
            return;
        }

        $agus->update(['tanggal_bergabung' => '2026-06-08']);

        // ── BUDI (Pagi, gaji 3.000.000, karyawan lama - gaji penuh) ────────
        // 1× telat dalam toleransi (5mnt, tidak dipotong), 1× telat luar
        // toleransi (25mnt → kena 15mnt), 1 alpha, 1 sakit + 1 izin (= kuota,
        // TIDAK dipotong), 5 jam lembur approved + 1 jam lembur pending
        // (harus DIABAIKAN dari perhitungan).
        $this->absen($budi, '2026-05-26', 'hadir', 0);
        $this->absen($budi, '2026-05-27', 'hadir', 5);
        $this->absen($budi, '2026-05-28', 'hadir', 25);
        $this->absen($budi, '2026-05-29', 'sakit');
        $this->absen($budi, '2026-06-01', 'izin');
        $this->absen($budi, '2026-06-02', 'tidak_hadir');
        $this->absen($budi, '2026-06-03', 'hadir', 0);
        $this->absen($budi, '2026-06-10', 'hadir', 0);
        $this->absen($budi, '2026-06-15', 'hadir', 0);
        $this->absen($budi, '2026-06-20', 'hadir', 0);
        $this->absen($budi, '2026-06-24', 'hadir', 0);
        $this->lembur($budi, '2026-05-26', '15:00', '17:00', 'approved'); // 2 jam
        $this->lembur($budi, '2026-06-05', '13:00', '16:00', 'approved'); // 3 jam
        $this->lembur($budi, '2026-06-12', '08:00', '09:00', 'pending');  // diabaikan

        // ── SITI (Siang, gaji 2.800.000, karyawan lama - gaji penuh) ───────
        // Tidak ada telat kena potong. 2 sakit + 2 izin = 4 kejadian, kuota 2
        // → 2 KELEBIHAN kuota (dipotong 2 × 0.5 hari). 4 jam lembur approved.
        $this->absen($siti, '2026-05-26', 'hadir', 0);
        $this->absen($siti, '2026-05-28', 'hadir', 0);
        $this->absen($siti, '2026-05-30', 'sakit');
        $this->absen($siti, '2026-06-02', 'sakit');
        $this->absen($siti, '2026-06-05', 'izin');
        $this->absen($siti, '2026-06-08', 'izin');
        $this->absen($siti, '2026-06-11', 'hadir', 8); // dalam toleransi, tidak dipotong
        $this->absen($siti, '2026-06-15', 'hadir', 0);
        $this->absen($siti, '2026-06-20', 'hadir', 0);
        $this->lembur($siti, '2026-06-01', '13:00', '17:00', 'approved'); // 4 jam

        // ── AGUS (Malam, gaji 3.200.000, BARU bergabung 2026-06-08) ────────
        // Prorate: gaji_pokok_prorate dihitung dari 8 Jun s/d 25 Jun saja.
        // 1× telat luar toleransi kecil (12mnt → kena 2mnt), 1 sakit (dalam
        // kuota, tidak dipotong), 1 jam lembur approved.
        $this->absen($agus, '2026-06-08', 'hadir', 0);
        $this->absen($agus, '2026-06-09', 'hadir', 12);
        $this->absen($agus, '2026-06-15', 'hadir', 0);
        $this->absen($agus, '2026-06-20', 'sakit');
        $this->absen($agus, '2026-06-22', 'hadir', 0);
        $this->lembur($agus, '2026-06-18', '07:00', '08:00', 'approved'); // 1 jam

        $this->command->info('PayrollDemoSeeder selesai: data absensi & lembur periode 26 Mei - 25 Jun 2026 siap untuk generate draft payroll.');
    }

    private function absen(User $user, string $tanggal, string $status, int $menitTelat = 0): void
    {
        Absensis::updateOrCreate(
            ['user_id' => $user->id, 'tanggal' => $tanggal],
            [
                'shift'        => $user->shift_default ?? 'Pagi',
                'status_hadir' => $status,
                'jam_masuk'    => $status === 'hadir' ? '08:00:00' : null,
                'jam_keluar'   => $status === 'hadir' ? '16:00:00' : null,
                'menit_telat'  => $status === 'hadir' ? $menitTelat : 0,
                'keterangan'   => 'Seed data demo payroll',
            ]
        );
    }

    private function lembur(User $user, string $tanggal, string $jamMulai, string $jamSelesai, string $status): void
    {
        Lembur::updateOrCreate(
            ['user_id' => $user->id, 'tanggal' => $tanggal, 'jam_mulai' => $jamMulai],
            [
                'jam_selesai'      => $jamSelesai,
                'alasan'           => 'Seed data demo payroll',
                'status'           => $status,
                'disetujui_oleh'   => $status === 'approved' ? User::where('role', 'manager')->value('id') : null,
                'catatan_approval' => $status === 'approved' ? 'Auto-approved (seed)' : null,
            ]
        );
    }
}
