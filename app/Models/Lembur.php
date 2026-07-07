<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lembur extends Model
{
    use HasFactory;

    protected $table = 'lembur';

    protected $fillable = [
        'user_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'alasan',
        'status', 'disetujui_oleh', 'catatan_approval',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    // Durasi dalam menit - jam_selesai < jam_mulai berarti lintas tengah malam
    // (mis. 22:00-02:00), jadi jam_selesai dianggap jatuh di hari berikutnya.
    public function getDurasiMenitAttribute(): int
    {
        $mulai   = \Carbon\Carbon::parse($this->jam_mulai);
        $selesai = \Carbon\Carbon::parse($this->jam_selesai);

        if ($selesai->lessThanOrEqualTo($mulai)) {
            $selesai->addDay();
        }

        return $mulai->diffInMinutes($selesai);
    }

    public function getDurasiLabelAttribute(): string
    {
        $jam       = intdiv($this->durasi_menit, 60);
        $sisaMenit = $this->durasi_menit % 60;

        return $sisaMenit > 0 ? "{$jam} jam {$sisaMenit} menit" : "{$jam} jam";
    }
}
