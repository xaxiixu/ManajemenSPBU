@extends('layouts.app')
@section('title', 'Buku Besar')
@section('page-title', 'Buku Besar')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-book-fill me-2 text-danger"></i>Buku Besar</h2>
    <p>Rekap transaksi per akun COA</p>
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Pilih Akun</label>
                <select name="coa_id" class="form-select" required>
                    <option value="">-- Pilih Akun COA --</option>
                    @foreach($coas as $coa)
                    <option value="{{ $coa->id }}"
                        {{ $coaId == $coa->id ? 'selected' : '' }}>
                        {{ $coa->kode_akun }} — {{ $coa->nama_akun }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

@if($selectedCoa)
{{-- Info Akun --}}
<div class="card mb-3" style="border-left: 4px solid #c0392b;">
    <div class="card-body py-3">
        <div class="row">
            <div class="col-md-4">
                <small class="text-muted">Akun</small>
                <div class="fw-semibold">{{ $selectedCoa->kode_akun }} — {{ $selectedCoa->nama_akun }}</div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Kategori</small>
                <div class="fw-semibold text-capitalize">{{ $selectedCoa->kategori }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Total Debit</small>
                <div class="fw-semibold text-primary">Rp {{ number_format($totalDebit) }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Total Kredit</small>
                <div class="fw-semibold text-success">Rp {{ number_format($totalKredit) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Saldo --}}
@php
    $posisiNormal = $selectedCoa->posisi_normal;
@endphp
<div class="card mb-3" style="background:#1a1a2e; color:#fff;">
    <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <span>Saldo Akhir ({{ ucfirst($posisiNormal) }})</span>
        <strong class="fs-5">Rp {{ number_format($saldoAkhir) }}</strong>
    </div>
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>No. Jurnal</th>
                    <th>Keterangan</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Kredit</th>
                    <th class="text-end">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-light">
                    <td>{{ \Carbon\Carbon::parse($bulan . '-01')->format('d/m/Y') }}</td>
                    <td>-</td>
                    <td class="fw-semibold">Saldo Awal</td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end fw-semibold">Rp {{ number_format($saldoAwal) }}</td>
                </tr>
                @php $saldoBerjalan = $saldoAwal; @endphp
                @forelse($data as $item)
                @php
                    if ($posisiNormal == 'debit') {
                        $saldoBerjalan += $item->posisi == 'debit' ? $item->jumlah : -$item->jumlah;
                    } else {
                        $saldoBerjalan += $item->posisi == 'kredit' ? $item->jumlah : -$item->jumlah;
                    }
                @endphp
                <tr>
                    <td>{{ $item->jurnal->tanggal->format('d/m/Y') }}</td>
                    <td><code>{{ $item->jurnal->nomor_jurnal }}</code></td>
                    <td>{{ $item->keterangan }}</td>
                    <td class="text-end">
                        {{ $item->posisi == 'debit' ? 'Rp ' . number_format($item->jumlah) : '-' }}
                    </td>
                    <td class="text-end">
                        {{ $item->posisi == 'kredit' ? 'Rp ' . number_format($item->jumlah) : '-' }}
                    </td>
                    <td class="text-end fw-semibold">Rp {{ number_format($saldoBerjalan) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        Tidak ada transaksi untuk akun ini di periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($data->count() > 0)
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th class="text-end">Rp {{ number_format($totalDebit) }}</th>
                    <th class="text-end">Rp {{ number_format($totalKredit) }}</th>
                    <th class="text-end">Rp {{ number_format($saldoAkhir) }}</th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-book text-muted" style="font-size:3rem;opacity:0.3;"></i>
        <h5 class="mt-3 text-muted">Pilih akun COA untuk melihat buku besar</h5>
        <p class="text-muted small mb-0">Pilih akun dan bulan di atas lalu klik Tampilkan</p>
    </div>
</div>
@endif
@endsection