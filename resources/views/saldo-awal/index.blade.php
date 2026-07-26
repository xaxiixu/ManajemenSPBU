@extends('layouts.app')
@section('title', 'Saldo Awal')
@section('page-title', 'Saldo Awal')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-piggy-bank-fill me-2 text-info"></i>Saldo Awal Pembukuan</h2>
    <p>Saldo awal sudah pernah diatur dan terkunci — tidak bisa diinput ulang lewat halaman ini.</p>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-3" style="border-left: 4px solid #0dcaf0;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="text-muted small">Nomor Jurnal</div>
                <div class="fw-bold fs-5"><code>{{ $jurnal->nomor_jurnal }}</code></div>
            </div>
            <div>
                <div class="text-muted small">Tanggal Saldo Awal</div>
                <div class="fw-bold fs-5">{{ $jurnal->tanggal->format('d/m/Y') }}</div>
            </div>
            <div>
                <div class="text-muted small">Dicatat Oleh</div>
                <div class="fw-bold fs-5">{{ $jurnal->dibuatOleh->name ?? '-' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="fw-bold text-uppercase mb-3" style="letter-spacing:.05em;">
            <i class="bi bi-journal-text me-2"></i>Rincian Jurnal Saldo Awal
        </h6>
        <table class="table table-borderless mb-0">
            <thead class="table-light">
                <tr>
                    <th>Akun</th>
                    <th>Keterangan</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jurnal->details as $d)
                <tr>
                    <td>{{ $d->coa->kode_akun }} {{ $d->coa->nama_akun }}</td>
                    <td class="text-muted small">{{ $d->keterangan }}</td>
                    <td class="text-end">{{ $d->posisi === 'debit' ? 'Rp '.number_format($d->jumlah) : '' }}</td>
                    <td class="text-end">{{ $d->posisi === 'kredit' ? 'Rp '.number_format($d->jumlah) : '' }}</td>
                </tr>
                @endforeach
                <tr class="border-top">
                    <td colspan="2" class="fw-bold">Total</td>
                    <td class="text-end fw-bold">Rp {{ number_format($jurnal->details->where('posisi', 'debit')->sum('jumlah')) }}</td>
                    <td class="text-end fw-bold">Rp {{ number_format($jurnal->details->where('posisi', 'kredit')->sum('jumlah')) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
