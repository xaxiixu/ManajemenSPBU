<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanBbm extends Model
{
    use HasFactory;

    protected $table = 'penjualan_bbm';

    protected $fillable = [
        'tanggal', 'shift', 'absensis_id', 'pulau', 'nozzle',
        'jenis_bbm', 'coa_pendapatan_id', 'meter_awal', 'meter_akhir',
        'liter_terjual', 'harga_per_liter', 'total_penjualan',
        'foto_meter_awal', 'foto_meter_akhir', 'catatan', 'dicatat_oleh',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function absensis()
    {
        return $this->belongsTo(Absensis::class, 'absensis_id');
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_pendapatan_id');
    }

    public function dicatatOleh()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($p) {
            if ($p->meter_akhir <= $p->meter_awal) {
                throw new \Exception('Meter akhir harus lebih besar dari meter awal.');
            }
            $p->liter_terjual   = $p->meter_akhir - $p->meter_awal;
            $p->total_penjualan = $p->liter_terjual * $p->harga_per_liter;
        });
    }

    public static function coaByJenisBbm(string $jenis): ?int
    {
        $map = [
            'Pertalite' => '4101-1',
            'Pertamax'  => '4101-2',
            'Solar'     => '4101-3',
        ];
        $kode = $map[$jenis] ?? null;
        return $kode ? Coa::where('kode_akun', $kode)->value('id') : null;
    }
}