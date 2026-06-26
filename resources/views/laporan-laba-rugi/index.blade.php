@extends('layouts.app')
@section('title', 'Laporan Laba & Rugi')
@section('page-title', 'Laporan Laba & Rugi')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-graph-up-arrow me-2 text-danger"></i>Laporan Laba & Rugi</h2>
        <p>Laporan pendapatan dan beban periode tertentu</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-danger">
        <i class="bi bi-printer me-1"></i> Print
    </button>
</div>

{{-- Filter --}}
<div class="card mb-3 d-print-none">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Pilih Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

{{-- Laporan --}}
<div class="card" id="laporanPrint">
    <div class="card-body">

        {{-- Header Print --}}
        <div class="text-center mb-4 d-none d-print-block">
            <h4 class="mb-0">SPBU Management System</h4>
            <h5>Laporan Laba & Rugi</h5>
            <p class="mb-0">Periode: {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}</p>
            <hr>
        </div>

        {{-- PENDAPATAN --}}
        <h6 class="fw-bold text-uppercase text-muted mb-3" style="letter-spacing:.05em;">
            Pendapatan
        </h6>
        <table class="table table-borderless mb-0">
            @foreach($pendapatan as $item)
            @if($item->total > 0)
            <tr>
                <td class="ps-3">{{ $item->nama_akun }}</td>
                <td class="text-end" width="30%">Rp {{ number_format($item->total) }}</td>
            </tr>
            @endif
            @endforeach
            <tr class="border-top">
                <td class="fw-bold ps-3">Total Pendapatan</td>
                <td class="text-end fw-bold text-success">Rp {{ number_format($totalPendapatan) }}</td>
            </tr>
        </table>

        <hr>

        {{-- BEBAN --}}
        <h6 class="fw-bold text-uppercase text-muted mb-3 mt-3" style="letter-spacing:.05em;">
            Beban Operasional
        </h6>
        <table class="table table-borderless mb-0">
            @foreach($beban as $item)
            @if($item->total > 0)
            <tr>
                <td class="ps-3">{{ $item->nama_akun }}</td>
                <td class="text-end" width="30%">Rp {{ number_format($item->total) }}</td>
            </tr>
            @endif
            @endforeach
            <tr class="border-top">
                <td class="fw-bold ps-3">Total Beban</td>
                <td class="text-end fw-bold text-danger">Rp {{ number_format($totalBeban) }}</td>
            </tr>
        </table>

        <hr>

        {{-- LABA / RUGI --}}
        <div class="p-3 rounded d-flex justify-content-between align-items-center"
            style="background: {{ $labaRugi >= 0 ? '#d4edda' : '#f8d7da' }};">
            <strong class="fs-5">{{ $labaRugi >= 0 ? '✅ Laba Bersih' : '❌ Rugi Bersih' }}</strong>
            <strong class="fs-4 {{ $labaRugi >= 0 ? 'text-success' : 'text-danger' }}">
                Rp {{ number_format(abs($labaRugi)) }}
            </strong>
        </div>

        @if($totalPendapatan == 0 && $totalBeban == 0)
        <div class="text-center py-4 text-muted">
            <i class="bi bi-info-circle me-2"></i>
            Belum ada transaksi untuk periode ini.
        </div>
        @endif

    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    #sidebar, #topbar, .d-print-none { display: none !important; }
    #main-content { margin: 0 !important; padding: 1rem !important; }
    .card { box-shadow: none !important; border: none !important; }
}
</style>
@endpush