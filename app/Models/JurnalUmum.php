<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalUmum extends Model
{
    use HasFactory;

    protected $table = 'jurnal_umum';

    protected $fillable = [
        'nomor_jurnal', 'tanggal', 'keterangan',
        'sumber', 'referensi_id', 'dibuat_oleh',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function details()
    {
        return $this->hasMany(JurnalDetail::class, 'jurnal_id');
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    // Auto generate nomor jurnal: JRN-20260518-0001
    public static function generateNomor(string $tanggal): string
    {
        $tgl    = \Carbon\Carbon::parse($tanggal)->format('Ymd');
        $prefix = 'JRN-' . $tgl . '-';
        $last   = self::where('nomor_jurnal', 'like', $prefix . '%')
            ->orderBy('nomor_jurnal', 'desc')
            ->value('nomor_jurnal');

        $urut = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($urut, 4, '0', STR_PAD_LEFT);
    }

    // Total debit jurnal ini
    public function getTotalDebitAttribute()
    {
        return $this->details->where('posisi', 'debit')->sum('jumlah');
    }

    // Total kredit jurnal ini
    public function getTotalKreditAttribute()
    {
        return $this->details->where('posisi', 'kredit')->sum('jumlah');
    }
}