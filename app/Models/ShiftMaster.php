<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftMaster extends Model
{
    use HasFactory;

    protected $table = 'shift_master';

    protected $fillable = ['shift', 'jam_mulai', 'jam_selesai'];
}
