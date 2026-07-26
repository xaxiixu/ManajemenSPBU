@extends('layouts.app')
@section('title', 'Stok Tangki')
@section('page-title', 'Stok Tangki')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-droplet-half me-2 text-info"></i>Stok Tangki</h2>
    <p>Kondisi stok & harga pokok rata-rata BBM per tangki saat ini</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tangki</th>
                    <th>Jenis BBM</th>
                    <th>Kapasitas</th>
                    <th>Stok Saat Ini</th>
                    <th>HPP Rata²</th>
                    <th>Estimasi Nilai Persediaan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tangkis as $t)
                <tr>
                    <td>{{ $t->nama_tangki }}</td>
                    <td>{{ $t->masterBbm->jenis_bbm ?? '-' }}</td>
                    <td>{{ number_format($t->kapasitas_liter) }} L</td>
                    <td>{{ number_format($t->stok_liter) }} L</td>
                    <td>Rp {{ number_format($t->harga_pokok_rata2) }}/L</td>
                    <td><strong>Rp {{ number_format($t->stok_liter * $t->harga_pokok_rata2) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        Belum ada tangki aktif.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
