<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBbm extends Model
{
    use HasFactory;

    protected $table = 'master_bbm';

    protected $fillable = [
        'jenis_bbm', 'ron', 'harga_per_liter', 'coa_pendapatan_id', 'coa_persediaan_id', 'is_aktif',
    ];

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_pendapatan_id');
    }

    public function coaPersediaan()
    {
        return $this->belongsTo(Coa::class, 'coa_persediaan_id');
    }

    public function penjualanBbm()
    {
        return $this->hasMany(PenjualanBbm::class, 'jenis_bbm', 'jenis_bbm');
    }
}
