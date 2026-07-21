<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPenyesuaian extends Model
{
    use HasFactory;

    protected $table = 'payroll_penyesuaian';

    protected $fillable = [
        'payroll_detail_id',
        'keterangan',
        'jumlah',
        'dibuat_oleh',
    ];

    protected $casts = [
        'jumlah' => 'integer',
    ];

    public function payrollDetail()
    {
        return $this->belongsTo(PayrollDetail::class, 'payroll_detail_id');
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function isBonus(): bool    { return $this->jumlah >= 0; }
    public function isPotongan(): bool { return $this->jumlah < 0; }
}
