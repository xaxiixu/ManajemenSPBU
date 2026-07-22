@extends('layouts.app')
@section('title', 'Detail Pembelian BBM')
@section('page-title', 'Detail Pembelian BBM')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-truck me-2 text-info"></i>Detail Pembelian BBM</h2>
        <p>{{ $pembelianBbm->tanggal->format('d F Y') }}</p>
    </div>
    <a href="{{ route('pembelian-bbm.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-header">Informasi Pembelian</div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <td class="text-muted" width="25%">Tanggal</td>
                <td>{{ $pembelianBbm->tanggal->format('d F Y') }}</td>
            </tr>
            <tr>
                <td class="text-muted">Supplier</td>
                <td>{{ $pembelianBbm->nama_supplier }}</td>
            </tr>
            <tr>
                <td class="text-muted">Tangki</td>
                <td>{{ $pembelianBbm->tangki->nama_tangki ?? '-' }} ({{ $pembelianBbm->tangki->masterBbm->jenis_bbm ?? '-' }})</td>
            </tr>
            <tr>
                <td class="text-muted">Volume</td>
                <td>{{ number_format($pembelianBbm->volume_liter) }} Liter</td>
            </tr>
            <tr>
                <td class="text-muted">Harga Beli / Liter</td>
                <td>Rp {{ number_format($pembelianBbm->harga_per_liter) }}</td>
            </tr>
            <tr>
                <td class="text-muted">Subtotal</td>
                <td><strong>Rp {{ number_format($pembelianBbm->subtotal) }}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Status Pembayaran</td>
                <td>
                    @if($pembelianBbm->status_bayar === 'tunai')
                        <span class="badge bg-success">Tunai</span>
                    @else
                        <span class="badge bg-warning text-dark">Kredit</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="text-muted">Dicatat oleh</td>
                <td>{{ $pembelianBbm->dicatatOleh->name ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>
@endsection
