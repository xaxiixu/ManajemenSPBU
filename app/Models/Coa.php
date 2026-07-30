<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

    protected $table = 'coa';

    protected $fillable = [
        'kode_akun', 'parent_id', 'nama_akun', 'kategori',
        'posisi_normal', 'deskripsi', 'is_aktif', 'saldo_awal',
    ];

    public function parent()
    {
        return $this->belongsTo(Coa::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Coa::class, 'parent_id')->orderBy('kode_akun');
    }

    public function penjualanBbm()
    {
        return $this->hasMany(PenjualanBbm::class, 'coa_pendapatan_id');
    }

    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class, 'coa_id');
    }
}