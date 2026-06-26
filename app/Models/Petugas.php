<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Petugas extends Model
{
    use HasFactory;

    protected $table = 'petugas';

    protected $fillable = [
        'nama', 'nik', 'jabatan',
        'shift_default', 'no_hp', 'is_aktif',
    ];

    public function absensis()
    {
        return $this->hasMany(Absensis::class, 'petugas_id');
    }
}