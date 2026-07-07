@extends('layouts.app')
@section('title', 'Detail Penjualan BBM')
@section('page-title', 'Detail Penjualan BBM')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-fuel-pump me-2 text-danger"></i>Detail Penjualan</h2>
        <p>{{ $penjualanBbm->tanggal->format('d F Y') }} — Shift {{ $penjualanBbm->shift }}</p>
    </div>
    <a href="{{ route('penjualan-bbm.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="row g-3">
    {{-- Info Utama --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Informasi Penjualan</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" width="40%">Tanggal</td><td>{{ $penjualanBbm->tanggal->format('d/m/Y') }}</td></tr>
                    <tr><td class="text-muted">Shift</td><td><span class="badge bg-secondary">{{ $penjualanBbm->shift }}</span></td></tr>
                    <tr><td class="text-muted">Pulau</td><td>{{ $penjualanBbm->pulau }}</td></tr>
                    <tr><td class="text-muted">Nozzle</td><td>{{ $penjualanBbm->nozzle }}</td></tr>
                    <tr><td class="text-muted">Jenis BBM</td><td>{{ $penjualanBbm->jenis_bbm }}</td></tr>
                    <tr><td class="text-muted">Operator</td><td>{{ $penjualanBbm->absensis->user->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Catatan</td><td>{{ $penjualanBbm->catatan ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Info Meter --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Data Meter & Penjualan</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" width="40%">Meter Awal</td><td>{{ number_format($penjualanBbm->meter_awal) }}</td></tr>
                    <tr><td class="text-muted">Meter Akhir</td><td>{{ number_format($penjualanBbm->meter_akhir) }}</td></tr>
                    <tr><td class="text-muted">Liter Terjual</td><td><strong>{{ number_format($penjualanBbm->liter_terjual) }} L</strong></td></tr>
                    <tr><td class="text-muted">Harga/Liter</td><td>Rp {{ number_format($penjualanBbm->harga_per_liter) }}</td></tr>
                    <tr>
                        <td class="text-muted">Total Penjualan</td>
                        <td><strong class="text-danger fs-5">Rp {{ number_format($penjualanBbm->total_penjualan) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Foto Meter --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Foto Meter Awal</div>
            <div class="card-body text-center">
                @if($penjualanBbm->foto_meter_awal)
                <img src="{{ asset('storage/' . $penjualanBbm->foto_meter_awal) }}"
                     class="img-fluid rounded" style="max-height:300px;"
                     alt="Foto Meter Awal">
                @else
                <p class="text-muted">Tidak ada foto</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Foto Meter Akhir</div>
            <div class="card-body text-center">
                @if($penjualanBbm->foto_meter_akhir)
                <img src="{{ asset('storage/' . $penjualanBbm->foto_meter_akhir) }}"
                     class="img-fluid rounded" style="max-height:300px;"
                     alt="Foto Meter Akhir">
                @else
                <p class="text-muted">Tidak ada foto</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection