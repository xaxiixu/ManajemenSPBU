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
            <div class="col-md-3">
                <label class="form-label fw-semibold">Filter Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('jurnal-umum.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
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
                    <th>Total Debit</th>
                    <th>Total Kredit</th>
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
                    <td>Rp {{ number_format($item->total_debit) }}</td>
                    <td>Rp {{ number_format($item->total_kredit) }}</td>
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