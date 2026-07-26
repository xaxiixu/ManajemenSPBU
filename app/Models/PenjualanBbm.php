<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanBbm extends Model
{
    use HasFactory;

    protected $table = 'penjualan_bbm';

    protected $fillable = [
        'tanggal', 'shift', 'absensis_id', 'pulau',
        'jenis_bbm', 'ron', 'coa_pendapatan_id', 'tangki_id', 'meter_awal', 'meter_akhir',
        'liter_terjual', 'harga_per_liter', 'hpp_per_liter', 'total_penjualan',
        'foto_meter_awal', 'foto_meter_akhir', 'catatan', 'dicatat_oleh',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function absensis()
    {
        return $this->belongsTo(Absensis::class, 'absensis_id');
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_pendapatan_id');
    }

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

        static::saving(function ($p) {
            if ($p->meter_akhir <= $p->meter_awal) {
                throw new \Exception('Meter akhir harus lebih besar dari meter awal.');
            }
            $p->liter_terjual   = $p->meter_akhir - $p->meter_awal;
            $p->total_penjualan = $p->liter_terjual * $p->harga_per_liter;

            // Sambungan ke stok tangki HANYA untuk transaksi BARU (create) -
            // jangan re-resolve/re-kurangi stok kalau suatu saat ada path yang
            // memanggil save() pada record yang sudah ada (tidak ada UI edit
            // penjualan saat ini, tapi guard ini murni jaga-jaga).
            if ($p->exists) {
                return;
            }

            // Resolve tangki dari jenis_bbm kalau belum di-set eksplisit oleh
            // pemanggil. Petugas tidak memilih tangki secara manual saat jual -
            // dispenser secara fisik sudah tersambung ke tangki jenis BBM
            // tertentu, jadi cukup 1 tangki aktif per jenis_bbm (sesuai seed
            // Fase 2). Kalau ada >1 tangki aktif untuk jenis yang sama, yang
            // pertama ditemukan yang dipakai - kasus ini di luar cakupan modul
            // saat ini (belum ada UI pemilihan tangki eksplisit untuk penjualan).
            if (! $p->tangki_id) {
                $masterBbmId = MasterBbm::where('jenis_bbm', $p->jenis_bbm)->value('id');
                $p->tangki_id = $masterBbmId
                    ? TangkiBbm::where('master_bbm_id', $masterBbmId)->where('is_aktif', 1)->value('id')
                    : null;
            }

            if (! $p->tangki_id) {
                // Jenis BBM ini belum punya tangki terdaftar - lewati snapshot
                // HPP & pengurangan stok, biarkan null (sama seperti data lama
                // sebelum modul persediaan ada).
                return;
            }

            $tangki = TangkiBbm::where('id', $p->tangki_id)->lockForUpdate()->first();

            // Cek stok cukup SEBELUM decrement - lockForUpdate() di atas
            // memastikan stok_liter yang dibaca di sini sudah final (tidak ada
            // pembelian/penjualan lain yang menyusul di tengah pengecekan),
            // jadi aman dari race condition antar transaksi bersamaan.
            if ($p->liter_terjual > $tangki->stok_liter) {
                throw new \Exception(
                    "Stok {$p->jenis_bbm} tidak mencukupi. Sisa stok: {$tangki->stok_liter} liter, permintaan: {$p->liter_terjual} liter."
                );
            }

            // Snapshot harga pokok rata-rata SAAT INI sebagai cost basis
            // transaksi ini (penjualan tidak mengubah harga_pokok_rata2 -
            // hanya pembelian yang mengubahnya - jadi urutan snapshot vs
            // pengurangan stok di bawah ini tidak berpengaruh pada angkanya).
            $p->hpp_per_liter = $tangki->harga_pokok_rata2;

            $tangki->decrement('stok_liter', $p->liter_terjual);
        });
    }
}