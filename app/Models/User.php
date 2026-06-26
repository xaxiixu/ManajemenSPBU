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
    public function isIT(): bool       { return $this->role === 'it'; }
    public function canEdit(): bool    { return in_array($this->role, ['it', 'pengawas', 'manager']); }
    public function isAdminLevel(): bool { return in_array($this->role, ['it', 'manager']); }
}