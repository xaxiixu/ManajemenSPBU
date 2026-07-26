@extends('layouts.app')
@section('title', 'Neraca')
@section('page-title', 'Neraca')

@section('content')

{{-- Filter --}}
<div class="card mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Per Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
            </div>
            <div class="col-md-2">
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
            </div>
        </form>
        <small class="text-muted mt-2 d-block">
            <i class="bi bi-info-circle me-1"></i>Neraca adalah snapshot saldo per satu tanggal (dihitung dari seluruh jurnal sejak awal s/d tanggal ini), bukan rekap satu periode seperti Laporan Laba Rugi.
        </small>
    </div>
</div>

{{-- Header Print --}}
<div class="d-none d-print-block text-center mb-4">
    <h4 class="mb-0 fw-bold">SPBU Management System</h4>
    <h5>Neraca (Balance Sheet)</h5>
    <p class="mb-0 text-muted">Per Tanggal: {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</p>
    <hr>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid #2980b9;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:#e8f4fd;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-bank text-primary fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Aktiva</div>
                        <div class="fw-bold fs-5" style="color:#2980b9;">Rp {{ number_format($totalAset) }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            Per {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid #8e44ad;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:#f1e8fb;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-diagram-3-fill text-purple fs-4" style="color:#8e44ad;"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Pasiva</div>
                        <div class="fw-bold fs-5" style="color:#8e44ad;">Rp {{ number_format($totalPasiva) }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            Kewajiban + Modal
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid {{ $selisih === 0 ? '#27ae60' : '#e74c3c' }};">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:{{ $selisih === 0 ? '#d4edda' : '#f8d7da' }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-{{ $selisih === 0 ? 'check-circle-fill' : 'exclamation-triangle-fill' }} {{ $selisih === 0 ? 'text-success' : 'text-danger' }} fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Balance Check</div>
                        @if($selisih === 0)
                        <span class="badge bg-success fs-6">Seimbang &#10003;</span>
                        @else
                        <span class="badge bg-danger fs-6">Tidak Seimbang</span>
                        <div class="text-danger fw-semibold" style="font-size:0.8rem;">
                            Selisih Rp {{ number_format(abs($selisih)) }} ({{ $selisih > 0 ? 'Aktiva lebih besar' : 'Pasiva lebih besar' }})
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Rincian --}}
<div class="row g-3">

    {{-- AKTIVA --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold text-uppercase mb-3" style="letter-spacing:.05em; color:#2980b9;">
                    <i class="bi bi-bank me-2"></i>Aktiva (Aset)
                </h6>
                <table class="table table-borderless mb-0">
                    @forelse($aset->where('saldo', '!=', 0) as $item)
                    <tr>
                        <td class="ps-3">
                            <span class="text-muted small">{{ $item->kode_akun }}</span>
                            {{ $item->nama_akun }}
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($item->saldo) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center text-muted ps-3">Belum ada saldo aset sampai tanggal ini</td></tr>
                    @endforelse
                    <tr class="border-top">
                        <td class="ps-3 fw-bold">Total Aktiva</td>
                        <td class="text-end fw-bold" style="color:#2980b9;">Rp {{ number_format($totalAset) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- PASIVA --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold text-uppercase mb-3" style="letter-spacing:.05em; color:#8e44ad;">
                    <i class="bi bi-diagram-3-fill me-2"></i>Pasiva (Kewajiban + Modal)
                </h6>

                {{-- Kewajiban --}}
                <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.03em;">Kewajiban</div>
                <table class="table table-borderless mb-0">
                    @forelse($kewajiban->where('saldo', '!=', 0) as $item)
                    <tr>
                        <td class="ps-3">
                            <span class="text-muted small">{{ $item->kode_akun }}</span>
                            {{ $item->nama_akun }}
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($item->saldo) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center text-muted ps-3">Belum ada saldo kewajiban sampai tanggal ini</td></tr>
                    @endforelse
                    <tr class="border-top">
                        <td class="ps-3 fw-bold">Total Kewajiban</td>
                        <td class="text-end fw-bold">Rp {{ number_format($totalKewajiban) }}</td>
                    </tr>
                </table>

                <hr class="my-3">

                {{-- Modal --}}
                <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.03em;">Modal</div>
                <table class="table table-borderless mb-0">
                    @forelse($modal->where('saldo', '!=', 0) as $item)
                    <tr>
                        <td class="ps-3">
                            <span class="text-muted small">{{ $item->kode_akun }}</span>
                            {{ $item->nama_akun }}
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($item->saldo) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center text-muted ps-3">Belum ada saldo modal sampai tanggal ini</td></tr>
                    @endforelse
                    <tr>
                        <td class="ps-3">
                            Laba/Rugi Berjalan
                            <span class="text-muted small d-block">(akumulasi s/d tanggal ini, belum ditutup ke Laba Ditahan)</span>
                        </td>
                        <td class="text-end fw-semibold {{ $labaBerjalan < 0 ? 'text-danger' : '' }}">
                            Rp {{ number_format($labaBerjalan) }}
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="ps-3 fw-bold">Total Modal</td>
                        <td class="text-end fw-bold">Rp {{ number_format($totalModal) }}</td>
                    </tr>
                </table>

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold" style="color:#8e44ad;">Total Pasiva (Kewajiban + Modal)</span>
                    <span class="fw-bold fs-5" style="color:#8e44ad;">Rp {{ number_format($totalPasiva) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Balance Check --}}
<div class="card mt-3">
    <div class="card-body p-4 rounded-3 d-flex justify-content-between align-items-center"
        style="background: {{ $selisih === 0 ? '#d4edda' : '#f8d7da' }};">
        <div>
            <div class="fw-bold fs-5 {{ $selisih === 0 ? 'text-success' : 'text-danger' }}">
                {{ $selisih === 0 ? '✅ Neraca Seimbang' : '⚠️ Neraca Tidak Seimbang' }}
            </div>
            <small class="text-muted">
                Total Aktiva vs Total Pasiva per {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
            </small>
        </div>
        <div class="text-end">
            @if($selisih === 0)
            <div class="fw-bold fs-4 text-success">Rp {{ number_format($totalAset) }} = Rp {{ number_format($totalPasiva) }}</div>
            @else
            <div class="fw-bold fs-4 text-danger">Selisih Rp {{ number_format(abs($selisih)) }}</div>
            <small class="text-muted">Aktiva Rp {{ number_format($totalAset) }} vs Pasiva Rp {{ number_format($totalPasiva) }}</small>
            @endif
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
@media print {
    #sidebar, #topbar, .d-print-none { display: none !important; }
    #main-content { margin: 0 !important; padding: 1rem !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
@endpush
