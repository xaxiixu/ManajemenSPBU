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
}
