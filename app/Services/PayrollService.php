<?php

namespace App\Services;

use App\Models\Absensis;
use App\Models\Lembur;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PayrollService — seluruh logika perhitungan penggajian petugas.
 *
 * Prinsip:
 *  - Murni HR, tidak menyentuh jurnal akuntansi.
 *  - hitung() bersifat PURE (tidak menulis DB) supaya gampang di-audit/ditest.
 *  - Angka di-snapshot ke payroll_details saat generate(), jadi histori tidak
 *    ikut berubah walau data absensi/setting/gaji_pokok diubah setelahnya.
 *  - Filter absensi & lembur ke periode memakai kolom `tanggal` apa adanya,
 *    karena kolom itu sudah menyimpan TANGGAL OPERASIONAL shift (shift lintas
 *    tengah malam sudah otomatis benar). Tidak ada logika hari-operasional
 *    tambahan di sini.
 */
class PayrollService
{
    // ─────────────────────────────────────────────────────────────────────
    //  PERIODE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Tanggal gajian bulan tertentu, di-clamp ke hari terakhir bulan kalau
     * tanggal_gajian melebihi jumlah hari bulan itu (mis. 31 di Februari → 28/29).
     */
    private function tanggalGajian(int $tahun, int $bulan, int $g): Carbon
    {
        $hariDalamBulan = Carbon::create($tahun, $bulan, 1)->daysInMonth;

        return Carbon::create($tahun, $bulan, min($g, $hariDalamBulan))->startOfDay();
    }

    /**
     * Diberi tanggal gajian akhir sebuah periode, kembalikan tanggal MULAI-nya:
     * yaitu satu hari SETELAH tanggal gajian bulan sebelumnya.
     * Contoh (g=25): selesai 25 Jul → mulai = 25 Jun + 1 hari = 26 Jun.
     */
    private function mulaiUntukSelesai(Carbon $selesai, int $g): Carbon
    {
        $bulanSebelum = $selesai->copy()->subMonthNoOverflow();

        return $this->tanggalGajian($bulanSebelum->year, $bulanSebelum->month, $g)->addDay();
    }

    /**
     * Periode payroll "saat ini" relatif ke $acuan (default hari ini):
     * periode yang BERAKHIR pada tanggal gajian terdekat yang >= $acuan
     * (keputusan desain: menargetkan gajian berikutnya). Kalau tanggal $acuan
     * sudah melewati tanggal gajian bulan ini, targetnya jadi gajian bulan depan.
     *
     * @return array{mulai: Carbon, selesai: Carbon}
     */
    public function periodeSaatIni(?PayrollSetting $setting = null, ?Carbon $acuan = null): array
    {
        $setting = $setting ?? PayrollSetting::get();
        $g       = $setting->tanggal_gajian;
        $acuan   = ($acuan ?? Carbon::today())->copy()->startOfDay();

        $gajianBulanIni = $this->tanggalGajian($acuan->year, $acuan->month, $g);

        if ($acuan->lessThanOrEqualTo($gajianBulanIni)) {
            $selesai = $gajianBulanIni;
        } else {
            $bulanDepan = $acuan->copy()->addMonthNoOverflow();
            $selesai    = $this->tanggalGajian($bulanDepan->year, $bulanDepan->month, $g);
        }

        return [
            'mulai'   => $this->mulaiUntukSelesai($selesai, $g),
            'selesai' => $selesai,
        ];
    }

    /**
     * Daftar kandidat periode untuk halaman Penggajian: periode saat ini plus
     * beberapa periode ke belakang, masing-masing dilengkapi run yang sudah ada
     * (kalau ada). Dipakai supaya manager bisa men-generate periode lampau yang
     * terlewat (belum pernah 'dikirim'). Urut terbaru dulu.
     *
     * @return array<int, array{mulai: Carbon, selesai: Carbon, run: ?PayrollRun}>
     */
    public function daftarKandidatPeriode(int $jumlahMundur = 6, ?PayrollSetting $setting = null): array
    {
        $setting = $setting ?? PayrollSetting::get();
        $g       = $setting->tanggal_gajian;

        $periodeIni = $this->periodeSaatIni($setting);
        $selesai    = $periodeIni['selesai'];

        $hasil = [];
        for ($i = 0; $i < $jumlahMundur; $i++) {
            $mulai = $this->mulaiUntukSelesai($selesai, $g);

            $run = PayrollRun::whereDate('periode_mulai', $mulai)
                ->whereDate('periode_selesai', $selesai)
                ->first();

            $hasil[] = ['mulai' => $mulai, 'selesai' => $selesai, 'run' => $run];

            // Mundur satu periode: selesai berikutnya = mulai - 1 hari
            $selesai = $mulai->copy()->subDay();
        }

        return $hasil;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PERHITUNGAN PER PETUGAS (PURE — tidak menulis DB)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Hitung seluruh komponen gaji satu petugas untuk sebuah periode.
     * total_penyesuaian selalu 0 di sini (penyesuaian manual ditambahkan
     * belakangan saat draft, lihat PayrollDetail::refreshTotal()).
     *
     * @return array<string, int>  siap dipakai untuk isi payroll_details
     */
    public function hitung(User $petugas, Carbon $periodeMulai, Carbon $periodeSelesai, PayrollSetting $setting): array
    {
        $periodeMulai   = $periodeMulai->copy()->startOfDay();
        $periodeSelesai = $periodeSelesai->copy()->startOfDay();

        // ── (0) Jumlah hari kalender dalam periode (inklusif) ──────────────
        // 26 Jun s/d 25 Jul = 30 hari. Dipakai sebagai pembagi rate harian,
        // supaya nominal potongan menyesuaikan panjang bulan.
        $totalHariPeriode = $periodeMulai->diffInDays($periodeSelesai) + 1;

        $gajiPokok = (int) ($petugas->gaji_pokok ?? 0);

        // Rate harian = gaji pokok PENUH ÷ jumlah hari periode. Sengaja pakai
        // gaji pokok penuh (bukan yang sudah di-prorate) sesuai aturan bisnis,
        // supaya nilai potongan per hari konsisten untuk semua petugas.
        $rateHarian = $totalHariPeriode > 0 ? $gajiPokok / $totalHariPeriode : 0;

        // ── (1) Gaji pokok prorate (petugas baru) ──────────────────────────
        // Prorate HANYA kalau tanggal_bergabung jatuh DI DALAM periode (setelah
        // hari pertama periode). Kalau bergabung sebelum/pas awal periode, atau
        // tidak diketahui (null), pakai gaji penuh.
        $gajiPokokProrate = $gajiPokok;
        $tanggalBergabung = $petugas->tanggal_bergabung
            ? $petugas->tanggal_bergabung->copy()->startOfDay()
            : null;

        if ($tanggalBergabung
            && $tanggalBergabung->greaterThan($periodeMulai)
            && $tanggalBergabung->lessThanOrEqualTo($periodeSelesai)) {
            // Hari kerja efektif = tanggal_bergabung s/d akhir periode (inklusif).
            $hariEfektif      = $tanggalBergabung->diffInDays($periodeSelesai) + 1;
            $gajiPokokProrate = (int) round($rateHarian * $hariEfektif);
        }

        // ── (2) Ambil semua record absensi dalam periode ───────────────────
        // Pakai kolom `tanggal` langsung (sudah tanggal operasional shift).
        // whereDate pada kedua batas → inklusif & aman lintas driver (sqlite
        // menyimpan kolom date sebagai '...00:00:00', jadi whereBetween string
        // bisa mengecualikan tanggal batas akhir; whereDate menghindari itu).
        $absensi = Absensis::where('user_id', $petugas->id)
            ->whereDate('tanggal', '>=', $periodeMulai->toDateString())
            ->whereDate('tanggal', '<=', $periodeSelesai->toDateString())
            ->get();

        $jumlahHadir = 0;
        $jumlahAlpha = 0;
        $jumlahSakit = 0;
        $jumlahIzin  = 0;

        // ── (3) Potongan telat ─────────────────────────────────────────────
        // Untuk tiap record: kalau menit_telat > toleransi, HANYA kelebihannya
        // (menit_telat − toleransi) yang dipotong, diakumulasi lintas record.
        // Praktisnya hanya record 'hadir' yang punya menit_telat > 0.
        $toleransi           = (int) $setting->toleransi_telat_menit;
        $jumlahKaliTelat     = 0;
        $totalMenitKenaPotong = 0;

        foreach ($absensi as $row) {
            switch ($row->status_hadir) {
                case 'hadir':       $jumlahHadir++; break;
                case 'tidak_hadir': $jumlahAlpha++; break;   // alpha
                case 'sakit':       $jumlahSakit++; break;
                case 'izin':        $jumlahIzin++;  break;
            }

            $telat = (int) ($row->menit_telat ?? 0);
            if ($telat > $toleransi) {
                $totalMenitKenaPotong += ($telat - $toleransi);
                $jumlahKaliTelat++;
            }
        }

        $potonganTelat = $totalMenitKenaPotong * (int) $setting->rate_potongan_telat_per_menit;

        // ── (4) Potongan ketidakhadiran ────────────────────────────────────
        // Alpha (tidak_hadir): potong PENUH 1 hari kerja per kejadian.
        $potonganAlpha = (int) round($rateHarian * $jumlahAlpha);

        // Sakit + izin: total kejadian dalam periode. Yang masih dalam kuota
        // tidak dipotong; yang MELEBIHI kuota dipotong SETENGAH hari kerja per
        // kejadian kelebihan.
        $kuota            = (int) $setting->kuota_izin_sakit_per_bulan;
        $totalSakitIzin   = $jumlahSakit + $jumlahIzin;
        $kelebihanKuota   = max(0, $totalSakitIzin - $kuota);
        $potonganSakitIzin = (int) round($rateHarian * 0.5 * $kelebihanKuota);

        $potonganAbsen = $potonganAlpha + $potonganSakitIzin;

        // ── (5) Uang lembur ────────────────────────────────────────────────
        // Semua record lembur 'approved' dalam periode; jumlahkan durasi (jam)
        // × rate. Durasi per record selalu kelipatan jam penuh (1-4 dari dropdown),
        // jadi total menit selalu kelipatan 60 — pembagian ke jam pasti bulat.
        $totalMenitLembur = Lembur::where('user_id', $petugas->id)
            ->where('status', 'approved')
            ->whereDate('tanggal', '>=', $periodeMulai->toDateString())
            ->whereDate('tanggal', '<=', $periodeSelesai->toDateString())
            ->get()
            ->sum(fn ($l) => $l->durasi_menit);

        $jumlahJamLembur = (int) round($totalMenitLembur / 60);
        $uangLembur      = $jumlahJamLembur * (int) $setting->rate_lembur_per_jam;

        // ── (6) Subtotal & gaji bersih (sebelum penyesuaian manual) ────────
        // subtotal = prorate − potongan telat − potongan absen + uang lembur.
        // Gaji bersih di-floor ke 0 (keputusan desain: tidak pernah negatif).
        $subtotal        = $gajiPokokProrate - $potonganTelat - $potonganAbsen + $uangLembur;
        $totalGajiBersih = max(0, $subtotal);

        return [
            'gaji_pokok_prorate'            => $gajiPokokProrate,
            'jumlah_hadir'                  => $jumlahHadir,
            'jumlah_kali_telat'             => $jumlahKaliTelat,
            'total_menit_telat_kena_potong' => $totalMenitKenaPotong,
            'potongan_telat'                => $potonganTelat,
            'jumlah_alpha'                  => $jumlahAlpha,
            'jumlah_sakit'                  => $jumlahSakit,
            'jumlah_izin'                   => $jumlahIzin,
            'potongan_absen'                => $potonganAbsen,
            'jumlah_jam_lembur'             => $jumlahJamLembur,
            'uang_lembur'                   => $uangLembur,
            'total_penyesuaian'             => 0,
            'total_gaji_bersih'             => $totalGajiBersih,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  GENERATE DRAFT
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Petugas yang MASUK payroll periode ini: role petugas, aktif, dan sudah
     * bergabung paling lambat di akhir periode (belum bergabung = dikecualikan).
     * Petugas nonaktif otomatis tidak ikut run baru; histori run lama tetap utuh.
     */
    private function petugasUntukPeriode(Carbon $periodeSelesai)
    {
        return User::where('role', 'petugas')
            ->where('is_active', 1)
            ->where(function ($q) use ($periodeSelesai) {
                $q->whereNull('tanggal_bergabung')
                  ->orWhereDate('tanggal_bergabung', '<=', $periodeSelesai->toDateString());
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Generate (atau regenerate) draft payroll untuk sebuah periode.
     * - Kalau sudah ada run 'dikirim' untuk periode ini → dilempar exception
     *   (tidak boleh double payroll; dicegah juga oleh unique index).
     * - Draft lama periode sama dihapus dulu (beserta detail & penyesuaian-nya)
     *   lalu dihitung ulang dari nol.
     */
    public function generateDraft(Carbon $periodeMulai, Carbon $periodeSelesai, int $dibuatOleh, ?PayrollSetting $setting = null): PayrollRun
    {
        $setting        = $setting ?? PayrollSetting::get();
        $periodeMulai   = $periodeMulai->copy()->startOfDay();
        $periodeSelesai = $periodeSelesai->copy()->startOfDay();

        return DB::transaction(function () use ($periodeMulai, $periodeSelesai, $dibuatOleh, $setting) {
            // Cegah generate kalau periode ini sudah pernah dikirim (final).
            $sudahDikirim = PayrollRun::whereDate('periode_mulai', $periodeMulai)
                ->whereDate('periode_selesai', $periodeSelesai)
                ->where('status', 'dikirim')
                ->exists();

            if ($sudahDikirim) {
                throw new \RuntimeException('Payroll periode ini sudah dikirim dan tidak bisa di-generate ulang.');
            }

            // Regenerate: buang draft lama periode sama (cascade → details & penyesuaian).
            PayrollRun::whereDate('periode_mulai', $periodeMulai)
                ->whereDate('periode_selesai', $periodeSelesai)
                ->where('status', 'draft')
                ->get()
                ->each->delete();

            $run = PayrollRun::create([
                'periode_mulai'   => $periodeMulai,
                'periode_selesai' => $periodeSelesai,
                'status'          => 'draft',
                'dibuat_oleh'     => $dibuatOleh,
            ]);

            foreach ($this->petugasUntukPeriode($periodeSelesai) as $petugas) {
                $hasil = $this->hitung($petugas, $periodeMulai, $periodeSelesai, $setting);

                PayrollDetail::create(array_merge($hasil, [
                    'payroll_run_id' => $run->id,
                    'user_id'        => $petugas->id,
                ]));
            }

            return $run->load('details.user');
        });
    }
}
