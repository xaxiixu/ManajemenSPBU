@extends('layouts.app')
@section('title', 'Detail Jurnal')
@section('page-title', 'Detail Jurnal')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-journal-text me-2 text-danger"></i>Detail Jurnal</h2>
        <p>{{ $jurnalUmum->nomor_jurnal }} — {{ $jurnalUmum->tanggal->format('d F Y') }}</p>
    </div>
    <a href="{{ route('jurnal-umum.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card mb-3">
    <div class="card-header">Informasi Jurnal</div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <td class="text-muted" width="20%">No. Jurnal</td>
                <td><code>{{ $jurnalUmum->nomor_jurnal }}</code></td>
            </tr>
            <tr>
                <td class="text-muted">Tanggal</td>
                <td>{{ $jurnalUmum->tanggal->format('d F Y') }}</td>
            </tr>
            <tr>
                <td class="text-muted">Keterangan</td>
                <td>{{ $jurnalUmum->keterangan }}</td>
            </tr>
            <tr>
                <td class="text-muted">Sumber</td>
                <td>
                    @if($jurnalUmum->sumber == 'penjualan_bbm')
                        <span class="badge bg-success">Penjualan BBM</span>
                    @elseif($jurnalUmum->sumber == 'pengeluaran')
                        <span class="badge bg-danger">Pengeluaran</span>
                    @else
                        <span class="badge bg-secondary">Manual</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">Baris Jurnal (Double Entry)</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th>Keterangan</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jurnalUmum->details as $detail)
                <tr>
                    <td><code>{{ $detail->coa->kode_akun }}</code></td>
                    <td>{{ $detail->coa->nama_akun }}</td>
                    <td class="text-muted small">{{ $detail->keterangan }}</td>
                    <td class="text-end">
                        {{ $detail->posisi == 'debit' ? 'Rp ' . number_format($detail->jumlah) : '-' }}
                    </td>
                    <td class="text-end">
                        {{ $detail->posisi == 'kredit' ? 'Rp ' . number_format($detail->jumlah) : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th class="text-end">Rp {{ number_format($jurnalUmum->total_debit) }}</th>
                    <th class="text-end">Rp {{ number_format($jurnalUmum->total_kredit) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection