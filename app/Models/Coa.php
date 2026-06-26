<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

    protected $table = 'coa';

    protected $fillable = [
        'kode_akun', 'nama_akun', 'kategori',
        'posisi_normal', 'deskripsi', 'is_aktif',
    ];

    public function penjualanBbm()
    {
        return $this->hasMany(PenjualanBbm::class, 'coa_pendapatan_id');
    }

    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class, 'coa_id');
    }
}