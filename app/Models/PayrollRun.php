<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $table = 'payroll_runs';

    protected $fillable = [
        'periode_mulai',
        'periode_selesai',
        'status',
        'dibuat_oleh',
        'dikirim_oleh',
        'dikirim_pada',
    ];

    protected $casts = [
        'periode_mulai'   => 'date',
        'periode_selesai' => 'date',
        'dikirim_pada'    => 'datetime',
    ];

    public function isDraft(): bool   { return $this->status === 'draft'; }
    public function isDikirim(): bool { return $this->status === 'dikirim'; }

    public function details()
    {
        return $this->hasMany(PayrollDetail::class, 'payroll_run_id');
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function dikirimOleh()
    {
        return $this->belongsTo(User::class, 'dikirim_oleh');
    }
}
