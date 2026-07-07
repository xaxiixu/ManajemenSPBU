<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanShift extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_shift';

    protected $fillable = [
        'user_id', 'shift_diminta', 'tanggal_berlaku', 'alasan',
        'status', 'disetujui_oleh', 'catatan_approval',
    ];

    protected $casts = ['tanggal_berlaku' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
