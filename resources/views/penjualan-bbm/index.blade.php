@extends('layouts.app')
@section('title', 'Penjualan BBM')
@section('page-title', 'Penjualan BBM')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-fuel-pump me-2 text-danger"></i>Penjualan BBM</h2>
        <p>Data penjualan bahan bakar minyak</p>
    </div>
    @if(in_array(auth()->user()->role, ['pengawas']))
    <a href="{{ route('penjualan-bbm.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah Data
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
            <div class="col-md-2">
                <label class="form-label fw-semibold">Atau Pilih Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Shift</label>
                <select name="shift" class="form-select">
                    <option value="">-- Semua Shift --</option>
                    @foreach(['Pagi', 'Siang', 'Malam'] as $s)
                    <option value="{{ $s }}" {{ $shift === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Jenis BBM</label>
                <select name="jenis_bbm" class="form-select">
                    <option value="">-- Semua Jenis --</option>
                    @foreach($jenisBbmOptions as $jb)
                    <option value="{{ $jb }}" {{ $jenisBbm === $jb ? 'selected' : '' }}>{{ $jb }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="{{ route('penjualan-bbm.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            </div>
        </form>
        <small class="text-muted mt-2 d-block">
            <i class="bi bi-info-circle me-1"></i>Jika filter bulan diisi, filter range tanggal akan diabaikan.
        </small>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Shift</th>
                    <th>Pulau</th>
                    <th>Jenis BBM</th>
                    <th>Liter Terjual</th>
                    <th>Total Penjualan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td><span class="badge bg-secondary">{{ $item->shift }}</span></td>
                    <td>P{{ $item->pulau }}</td>
                    <td>{{ $item->jenis_bbm }}@if($item->ron) <span class="text-muted small">(RON {{ $item->ron }})</span>@endif</td>
                    <td>{{ number_format($item->liter_terjual) }} L</td>
                    <td>Rp {{ number_format($item->total_penjualan) }}</td>
                    <td>
                        <a href="{{ route('penjualan-bbm.show', $item) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                        @if(in_array(auth()->user()->role, ['pengawas']))
                        <form action="{{ route('penjualan-bbm.destroy', $item) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Hapus data ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data penjualan untuk periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
