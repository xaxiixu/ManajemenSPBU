<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Absensis extends Model
{
    use HasFactory;

    protected $table = 'absensis';

    protected $fillable = [
        'user_id', 'tanggal', 'shift',
        'status_hadir', 'jam_masuk', 'jam_keluar', 'menit_telat',
        'menit_pulang_cepat', 'flag_kelebihan_waktu',
        'keterangan', 'dicatat_oleh',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dicatatOleh()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * Hitung menit telat terhadap jam_mulai shift, dengan tanggal referensi
     * jam_mulai dimundurkan satu hari kalau shift melewati tengah malam
     * (jam_selesai < jam_mulai, mis. Malam 23:00-07:00) dan jam masuk berada
     * di dini hari (sebelum jam_selesai shift) - supaya arrival jam 00:05
     * dihitung telat dari 23:00 hari sebelumnya, bukan dianggap tidak telat.
     */
    public static function hitungMenitTelat(ShiftMaster $shiftMaster, Carbon $tanggal, string $jamMasuk): int
    {
        $tglStr = $tanggal->format('Y-m-d');
        $tz = $tanggal->timezone;

        $masuk = Carbon::createFromFormat('Y-m-d H:i:s', "{$tglStr} {$jamMasuk}", $tz);
        $mulai = Carbon::createFromFormat('Y-m-d H:i:s', "{$tglStr} {$shiftMaster->jam_mulai}", $tz);

        $lewatTengahMalam = $shiftMaster->jam_selesai < $shiftMaster->jam_mulai;
        if ($lewatTengahMalam && $jamMasuk < $shiftMaster->jam_selesai) {
            $mulai->subDay();
        }

        $selisihDetik = $masuk->getTimestamp() - $mulai->getTimestamp();

        return $selisihDetik > 0 ? intdiv($selisihDetik, 60) : 0;
    }

    /**
     * Bandingkan jam_keluar sebenarnya dengan jam pulang yang diharapkan
     * (jam_selesai shift, atau jam_selesai lembur approved kalau ada - dioper
     * lewat $jamSelesaiLembur, menggantikan acuan shift biasa).
     *
     * Semua waktu di-anchor maju (forward-anchor) dari jam_mulai shift memakai
     * teknik yang sama seperti hitungMenitTelat(): kalau suatu jam lebih kecil
     * dari titik acuan sebelumnya, dianggap jatuh di hari berikutnya. Ini otomatis
     * benar untuk shift lintas tengah malam maupun lembur lintas tengah malam,
     * tanpa perlu tahu berapa kali "hari berganti" - selama total durasi shift+lembur
     * jauh di bawah 24 jam (asumsi wajar untuk shift SPBU + lembur maks 4 jam).
     *
     * Toleransi simetris (default 5 menit): selisih dalam rentang ini dianggap
     * tepat waktu, supaya tidak terlalu sensitif menandai anomali untuk selisih
     * beberapa menit yang wajar (mis. pembulatan jam, waktu jalan ke pos absen).
     */
    public static function hitungSelisihPulang(
        ShiftMaster $shiftMaster,
        Carbon $tanggal,
        string $jamKeluar,
        ?string $jamSelesaiLembur = null,
        int $toleransiMenit = 5
    ): array {
        $tglStr = $tanggal->format('Y-m-d');
        $tz = $tanggal->timezone;

        $mulaiShift = Carbon::createFromFormat('Y-m-d H:i:s', "{$tglStr} {$shiftMaster->jam_mulai}", $tz);

        $selesaiShift = $mulaiShift->copy()->setTimeFromTimeString($shiftMaster->jam_selesai);
        if ($selesaiShift->lessThanOrEqualTo($mulaiShift)) {
            $selesaiShift->addDay();
        }

        $jamHarapan = $selesaiShift;

        if ($jamSelesaiLembur) {
            $selesaiLembur = $selesaiShift->copy()->setTimeFromTimeString($jamSelesaiLembur);
            if ($selesaiLembur->lessThanOrEqualTo($selesaiShift)) {
                $selesaiLembur->addDay();
            }
            $jamHarapan = $selesaiLembur;
        }

        $keluar = $mulaiShift->copy()->setTimeFromTimeString($jamKeluar);
        if ($keluar->lessThanOrEqualTo($mulaiShift)) {
            $keluar->addDay();
        }

        $toleransiDetik = $toleransiMenit * 60;
        $selisihDetik   = $jamHarapan->getTimestamp() - $keluar->getTimestamp();

        if (abs($selisihDetik) <= $toleransiDetik) {
            return ['menit_pulang_cepat' => 0, 'flag_kelebihan_waktu' => false];
        }

        if ($selisihDetik > 0) {
            return ['menit_pulang_cepat' => intdiv($selisihDetik, 60), 'flag_kelebihan_waktu' => false];
        }

        return ['menit_pulang_cepat' => 0, 'flag_kelebihan_waktu' => true];
    }
}
