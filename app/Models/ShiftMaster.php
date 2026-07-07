<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftMaster extends Model
{
    use HasFactory;

    protected $table = 'shift_master';

    protected $fillable = ['shift', 'jam_mulai', 'jam_selesai'];

    /**
     * Tanggal "operasional" saat ini, beda dari tanggal kalender (today())
     * kalau sekarang masih dalam ekor shift lintas tengah malam (mis. Shift
     * Malam 23:00-07:00) yang dimulai KEMARIN. Dipakai supaya statistik
     * "hari ini" & dropdown operator konsisten dengan tanggal yang dipakai
     * saat presensi/transaksi shift tersebut dicatat.
     *
     * Shift lintas tengah malam dikenali dari data (jam_selesai < jam_mulai),
     * bukan hardcode jam - kalau ada beberapa shift lintas tengah malam
     * sekaligus dan overlap, yang dipakai adalah shift pertama yang cocok.
     */
    public static function tanggalOperasionalSaatIni(): array
    {
        $waktuSekarang = now()->format('H:i:s');

        $shiftLintas = static::get()
            ->first(fn ($s) => $s->jam_selesai < $s->jam_mulai && $waktuSekarang < $s->jam_selesai);

        if ($shiftLintas) {
            return [
                'tanggal'             => today()->subDay(),
                'shift_aktif'         => $shiftLintas->shift,
                'lintas_tengah_malam' => true,
            ];
        }

        return [
            'tanggal'             => today(),
            'shift_aktif'         => null,
            'lintas_tengah_malam' => false,
        ];
    }
}
