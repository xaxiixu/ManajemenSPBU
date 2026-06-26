<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensis extends Model
{
    use HasFactory;

    protected $table = 'absensis';

    protected $fillable = [
        'petugas_id', 'tanggal', 'shift',
        'status_hadir', 'jam_masuk', 'jam_keluar',
        'keterangan', 'dicatat_oleh',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function petugas()
    {
        return $this->belongsTo(Petugas::class, 'petugas_id');
    }

    public function dicatatOleh()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}