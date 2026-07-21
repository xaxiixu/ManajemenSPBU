<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    use HasFactory;

    protected $table = 'payroll_settings';

    protected $fillable = [
        'tanggal_gajian',
        'rate_lembur_per_jam',
        'rate_potongan_telat_per_menit',
        'toleransi_telat_menit',
        'kuota_izin_sakit_per_bulan',
    ];

    protected $casts = [
        'tanggal_gajian'                => 'integer',
        'rate_lembur_per_jam'           => 'integer',
        'rate_potongan_telat_per_menit' => 'integer',
        'toleransi_telat_menit'         => 'integer',
        'kuota_izin_sakit_per_bulan'    => 'integer',
    ];

    /**
     * Ambil satu-satunya baris pengaturan (single row). Kalau belum ada
     * (mis. seeder belum jalan), buat baris default on-the-fly supaya
     * halaman tidak error.
     */
    public static function get(): self
    {
        return static::first() ?? static::create([
            'tanggal_gajian'                => 25,
            'rate_lembur_per_jam'           => 20000,
            'rate_potongan_telat_per_menit' => 1000,
            'toleransi_telat_menit'         => 10,
            'kuota_izin_sakit_per_bulan'    => 2,
        ]);
    }
}
