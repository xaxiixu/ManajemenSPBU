<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    use HasFactory;

    protected $table = 'pengeluarans';

    protected $fillable = [
        'tanggal', 'coa_id', 'keterangan',
        'jumlah', 'bukti_pembayaran', 'dicatat_oleh',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_id');
    }

    public function dicatatOleh()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}