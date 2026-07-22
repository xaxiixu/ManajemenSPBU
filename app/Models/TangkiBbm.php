<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TangkiBbm extends Model
{
    use HasFactory;

    protected $table = 'tangki_bbm';

    protected $fillable = [
        'master_bbm_id', 'nama_tangki', 'kapasitas_liter',
        'stok_liter', 'harga_pokok_rata2', 'is_aktif',
    ];

    protected $casts = [
        'kapasitas_liter'   => 'integer',
        'stok_liter'        => 'integer',
        'harga_pokok_rata2' => 'integer',
        'is_aktif'          => 'boolean',
    ];

    public function masterBbm()
    {
        return $this->belongsTo(MasterBbm::class, 'master_bbm_id');
    }
}
