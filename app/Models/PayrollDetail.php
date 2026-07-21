<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $table = 'payroll_details';

    protected $fillable = [
        'payroll_run_id',
        'user_id',
        'gaji_pokok_prorate',
        'jumlah_hadir',
        'jumlah_kali_telat',
        'total_menit_telat_kena_potong',
        'potongan_telat',
        'jumlah_alpha',
        'jumlah_sakit',
        'jumlah_izin',
        'potongan_absen',
        'jumlah_jam_lembur',
        'uang_lembur',
        'total_penyesuaian',
        'total_gaji_bersih',
    ];

    protected $casts = [
        'gaji_pokok_prorate'            => 'integer',
        'jumlah_hadir'                  => 'integer',
        'jumlah_kali_telat'             => 'integer',
        'total_menit_telat_kena_potong' => 'integer',
        'potongan_telat'                => 'integer',
        'jumlah_alpha'                  => 'integer',
        'jumlah_sakit'                  => 'integer',
        'jumlah_izin'                   => 'integer',
        'potongan_absen'                => 'integer',
        'jumlah_jam_lembur'             => 'integer',
        'uang_lembur'                   => 'integer',
        'total_penyesuaian'             => 'integer',
        'total_gaji_bersih'             => 'integer',
    ];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penyesuaian()
    {
        return $this->hasMany(PayrollPenyesuaian::class, 'payroll_detail_id');
    }

    /**
     * Total potongan (telat + absen) - untuk tampilan ringkas.
     */
    public function getTotalPotonganAttribute(): int
    {
        return $this->potongan_telat + $this->potongan_absen;
    }

    /**
     * Subtotal sebelum penyesuaian manual:
     *   gaji pokok prorate − potongan telat − potongan absen + uang lembur.
     */
    public function getSubtotalAttribute(): int
    {
        return $this->gaji_pokok_prorate
            - $this->potongan_telat
            - $this->potongan_absen
            + $this->uang_lembur;
    }

    /**
     * Hitung ulang total_penyesuaian & total_gaji_bersih dari baris penyesuaian
     * yang tersimpan, lalu simpan. Dipanggil setelah penyesuaian manual
     * ditambah/diedit/dihapus (hanya boleh saat run masih draft). Gaji bersih
     * di-floor ke 0 (tidak pernah negatif).
     */
    public function refreshTotal(): void
    {
        $totalPenyesuaian = (int) $this->penyesuaian()->sum('jumlah');

        $this->total_penyesuaian = $totalPenyesuaian;
        $this->total_gaji_bersih = max(0, $this->subtotal + $totalPenyesuaian);
        $this->save();
    }
}
