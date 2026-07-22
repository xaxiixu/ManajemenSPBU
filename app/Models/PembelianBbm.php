<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PembelianBbm extends Model
{
    use HasFactory;

    protected $table = 'pembelian_bbm';

    protected $fillable = [
        'tanggal', 'nama_supplier', 'tangki_id',
        'volume_liter', 'harga_per_liter', 'subtotal',
        'status_bayar', 'dicatat_oleh',
    ];

    protected $casts = [
        'tanggal'         => 'date',
        'volume_liter'    => 'integer',
        'harga_per_liter' => 'integer',
        'subtotal'        => 'integer',
    ];

    public function tangki()
    {
        return $this->belongsTo(TangkiBbm::class, 'tangki_id');
    }

    public function dicatatOleh()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    protected static function boot()
    {
        parent::boot();

        // subtotal selalu dihitung ulang dari volume × harga - tidak dipercaya
        // dari input, sama seperti PenjualanBbm menghitung total_penjualan sendiri.
        static::saving(function (PembelianBbm $p) {
            $p->subtotal = $p->volume_liter * $p->harga_per_liter;
        });

        // SETELAH baris pembelian tersimpan: update stok & harga pokok rata-rata
        // (moving average) tangki terkait. Dibungkus DB::transaction sendiri
        // supaya tetap atomik walau dipanggil berdiri sendiri (mis. dari tinker
        // tanpa transaction pembungkus dari controller) - lockForUpdate mencegah
        // race condition kalau ada 2 pembelian ke tangki yang sama nyaris
        // bersamaan.
        static::created(function (PembelianBbm $p) {
            DB::transaction(function () use ($p) {
                $tangki = TangkiBbm::where('id', $p->tangki_id)->lockForUpdate()->first();

                $stokLama    = $tangki->stok_liter;
                $hppLama     = $tangki->harga_pokok_rata2;
                $volumeMasuk = $p->volume_liter;
                $hargaBeli   = $p->harga_per_liter;

                $stokBaru = $stokLama + $volumeMasuk;

                // Moving average: harga_baru = (stok_lama × hpp_lama + volume_masuk × harga_beli) / stok_baru.
                // Di-round ke rupiah terdekat, konsisten dengan seluruh nominal
                // uang lain di project (tidak ada kolom desimal untuk uang).
                $hppBaru = $stokBaru > 0
                    ? (int) round((($stokLama * $hppLama) + ($volumeMasuk * $hargaBeli)) / $stokBaru)
                    : 0;

                $tangki->update([
                    'stok_liter'        => $stokBaru,
                    'harga_pokok_rata2' => $hppBaru,
                ]);
            });
        });
    }
}
