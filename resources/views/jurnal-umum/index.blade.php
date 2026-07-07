@extends('layouts.app')
@section('title', 'Jurnal Umum')
@section('page-title', 'Jurnal Umum')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-journal-text me-2 text-danger"></i>Jurnal Umum</h2>
        <p>Jurnal transaksi keuangan otomatis</p>
    </div>
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
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">&nbsp;</label>
                <a href="{{ route('jurnal-umum.index') }}" class="btn btn-outline-secondary w-100">
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
                    <th>No. Jurnal</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Sumber</th>
                    <th class="text-end">Total Debit</th>
                    <th class="text-end">Total Kredit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td><code>{{ $item->nomor_jurnal }}</code></td>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $item->keterangan }}</td>
                    <td>
                        @if($item->sumber == 'penjualan_bbm')
                            <span class="badge bg-success">Penjualan BBM</span>
                        @elseif($item->sumber == 'pengeluaran')
                            <span class="badge bg-danger">Pengeluaran</span>
                        @else
                            <span class="badge bg-secondary">Manual</span>
                        @endif
                    </td>
                    <td class="text-end">Rp {{ number_format($item->total_debit) }}</td>
                    <td class="text-end">Rp {{ number_format($item->total_kredit) }}</td>
                    <td>
                        <a href="{{ route('jurnal-umum.show', $item) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Belum ada jurnal untuk periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection