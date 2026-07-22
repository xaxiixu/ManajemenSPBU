@extends('layouts.app')
@section('title', 'Pembelian BBM')
@section('page-title', 'Pembelian BBM')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-truck me-2 text-info"></i>Pembelian BBM</h2>
        <p>Data pembelian BBM dari supplier</p>
    </div>
    @if(auth()->user()->role === 'pengawas')
    <a href="{{ route('pembelian-bbm.create') }}" class="btn btn-info text-white">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pembelian
    </a>
    @endif
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control" value="{{ $dari }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control" value="{{ $sampai }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Atau Pilih Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">&nbsp;</label>
                <button type="submit" class="btn btn-info text-white w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">&nbsp;</label>
                <a href="{{ route('pembelian-bbm.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            </div>
        </form>
        <small class="text-muted mt-2 d-block">
            <i class="bi bi-info-circle me-1"></i>Jika filter bulan diisi, filter range tanggal akan diabaikan.
        </small>
    </div>
</div>

{{-- Total --}}
<div class="card mb-3" style="border-left: 4px solid #0dcaf0;">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Total Pembelian Periode Ini</span>
            <strong class="text-info fs-5">Rp {{ number_format($total) }}</strong>
        </div>
    </div>
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Tangki</th>
                    <th>Volume</th>
                    <th>Harga/L</th>
                    <th>Subtotal</th>
                    <th>Status Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $item->nama_supplier }}</td>
                    <td>{{ $item->tangki->nama_tangki ?? '-' }} <span class="text-muted small">({{ $item->tangki->masterBbm->jenis_bbm ?? '-' }})</span></td>
                    <td>{{ number_format($item->volume_liter) }} L</td>
                    <td>Rp {{ number_format($item->harga_per_liter) }}</td>
                    <td><strong>Rp {{ number_format($item->subtotal) }}</strong></td>
                    <td>
                        @if($item->status_bayar === 'tunai')
                            <span class="badge bg-success">Tunai</span>
                        @else
                            <span class="badge bg-warning text-dark">Kredit</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('pembelian-bbm.show', $item) }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Belum ada data pembelian BBM untuk periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
