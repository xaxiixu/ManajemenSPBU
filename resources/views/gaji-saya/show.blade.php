@extends('layouts.app')
@section('title', 'Slip Gaji')
@section('page-title', 'Slip Gaji')

@section('content')
@php
    $d       = $payrollDetail;
    $run     = $d->payrollRun;
    $gajiFull = $d->user->gaji_pokok ?? 0;
    $prorate = $d->gaji_pokok_prorate < $gajiFull;
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 mb-3 d-print-none">
    <a href="{{ route('gaji-saya.index') }}" class="btn btn-sm btn-link text-decoration-none ps-0">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print / Download
    </button>
</div>

<div class="card mx-auto" style="max-width:720px;">
    <div class="card-body p-4">
        {{-- Header slip --}}
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-0">SPBU Management System</h4>
            <h6 class="text-muted">Slip Gaji Petugas</h6>
        </div>

        <div class="row mb-3 small">
            <div class="col-6">
                <div class="text-muted">Nama</div>
                <div class="fw-semibold">{{ $d->user->name ?? '—' }}</div>
            </div>
            <div class="col-6 text-md-end">
                <div class="text-muted">Periode</div>
                <div class="fw-semibold">{{ $run->periode_mulai->translatedFormat('d M Y') }} — {{ $run->periode_selesai->translatedFormat('d M Y') }}</div>
            </div>
        </div>
        <div class="row mb-3 small">
            <div class="col-6">
                <div class="text-muted">NIK</div>
                <div class="fw-semibold">{{ $d->user->nik ?? '—' }}</div>
            </div>
            <div class="col-6 text-md-end">
                <div class="text-muted">Diterbitkan</div>
                <div class="fw-semibold">{{ $run->dikirim_pada?->translatedFormat('d M Y H:i') ?? '—' }}</div>
            </div>
        </div>

        <hr>

        {{-- Pendapatan --}}
        <h6 class="fw-bold text-success text-uppercase" style="letter-spacing:.05em; font-size:.8rem;">Pendapatan</h6>
        <table class="table table-sm mb-3">
            <tr>
                <td>
                    Gaji Pokok
                    @if($prorate)
                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.65rem;">prorate</span>
                        <div class="text-muted" style="font-size:.72rem;">
                            Prorate dari Rp {{ number_format($gajiFull) }} — bergabung {{ optional($d->user->tanggal_bergabung)->translatedFormat('d M Y') }}
                        </div>
                    @endif
                </td>
                <td class="text-end">Rp {{ number_format($d->gaji_pokok_prorate) }}</td>
            </tr>
            <tr>
                <td>Uang Lembur <span class="text-muted">({{ $d->jumlah_jam_lembur }} jam)</span></td>
                <td class="text-end text-success">+Rp {{ number_format($d->uang_lembur) }}</td>
            </tr>
        </table>

        {{-- Potongan --}}
        <h6 class="fw-bold text-danger text-uppercase" style="letter-spacing:.05em; font-size:.8rem;">Potongan</h6>
        <table class="table table-sm mb-3">
            <tr>
                <td>
                    Keterlambatan
                    @if($d->jumlah_kali_telat > 0)
                        <div class="text-muted" style="font-size:.72rem;">{{ $d->jumlah_kali_telat }}× telat, {{ $d->total_menit_telat_kena_potong }} menit kena potong</div>
                    @endif
                </td>
                <td class="text-end {{ $d->potongan_telat > 0 ? 'text-danger' : '' }}">−Rp {{ number_format($d->potongan_telat) }}</td>
            </tr>
            <tr>
                <td>
                    Ketidakhadiran
                    <div class="text-muted" style="font-size:.72rem;">
                        {{-- Hari tidak tercatat digabung ke "tidak hadir" (istilah internal tidak
                             ditampilkan ke petugas) agar rupiah potongan konsisten dengan jumlah hari. --}}
                        Tidak hadir {{ $d->jumlah_alpha + $d->jumlah_tidak_tercatat }} · Sakit {{ $d->jumlah_sakit }} · Izin {{ $d->jumlah_izin }} (hadir {{ $d->jumlah_hadir }})
                    </div>
                </td>
                <td class="text-end {{ $d->potongan_absen > 0 ? 'text-danger' : '' }}">−Rp {{ number_format($d->potongan_absen) }}</td>
            </tr>
        </table>

        {{-- Penyesuaian --}}
        @if($d->penyesuaian->count())
        <h6 class="fw-bold text-uppercase" style="letter-spacing:.05em; font-size:.8rem;">Penyesuaian</h6>
        <table class="table table-sm mb-3">
            @foreach($d->penyesuaian as $p)
            <tr>
                <td>{{ $p->keterangan }}</td>
                <td class="text-end {{ $p->jumlah < 0 ? 'text-danger' : 'text-success' }}">
                    {{ $p->jumlah >= 0 ? '+' : '−' }}Rp {{ number_format(abs($p->jumlah)) }}
                </td>
            </tr>
            @endforeach
        </table>
        @endif

        <hr>

        {{-- Total --}}
        <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background:#d4edda;">
            <div class="fw-bold text-success">GAJI BERSIH</div>
            <div class="fw-bold fs-4 text-success">Rp {{ number_format($d->total_gaji_bersih) }}</div>
        </div>

        <p class="text-muted mt-3 mb-0" style="font-size:.72rem;">
            Slip ini terbit otomatis dari sistem penggajian. Untuk pertanyaan mengenai perhitungan, hubungi manager.
        </p>
    </div>
</div>

@push('styles')
<style>
@media print {
    #sidebar, #topbar, .d-print-none { display: none !important; }
    #main-content { margin: 0 !important; padding: 1rem !important; }
    .card { box-shadow: none !important; border: none !important; max-width:100% !important; }
}
</style>
@endpush
@endsection
