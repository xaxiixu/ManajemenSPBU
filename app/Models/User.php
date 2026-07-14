<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'nik',
        'jabatan',
        'no_hp',
        'shift_default',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isManager(): bool  { return $this->role === 'manager'; }
    public function isPengawas(): bool { return $this->role === 'pengawas'; }
    public function isPetugas(): bool  { return $this->role === 'petugas'; }
    public function canEdit(): bool    { return in_array($this->role, ['pengawas', 'manager']); }
    public function isAdminLevel(): bool { return $this->role === 'manager'; }

    public function absensis()
    {
        return $this->hasMany(Absensis::class, 'user_id');
    }

    public function pengajuanShift()
    {
        return $this->hasMany(PengajuanShift::class, 'user_id');
    }

    public function lembur()
    {
        return $this->hasMany(Lembur::class, 'user_id');
    }
}