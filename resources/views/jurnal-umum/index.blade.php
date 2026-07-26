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
            <div class="col-md-3">
                <label class="form-label fw-semibold">Sumber</label>
                <select name="sumber" class="form-select">
                    <option value="">Semua Sumber</option>
                    @foreach($sumberOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sumber == $value)>{{ $label }}</option>
                    @endforeach
                </select>
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

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid #27ae60;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:#d4edda;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-arrow-down-circle text-success fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Debit</div>
                        <div class="fw-bold fs-5 text-success">Rp {{ number_format($totalDebit) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid #2980b9;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:#d6eaf8;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-arrow-up-circle text-primary fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Kredit</div>
                        <div class="fw-bold fs-5 text-primary">Rp {{ number_format($totalKredit) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid {{ $selisih == 0 ? '#27ae60' : '#e74c3c' }};">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:{{ $selisih == 0 ? '#d4edda' : '#f8d7da' }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-{{ $selisih == 0 ? 'check-circle' : 'exclamation-triangle' }} {{ $selisih == 0 ? 'text-success' : 'text-danger' }} fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Selisih</div>
                        <div class="fw-bold fs-5 {{ $selisih == 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format(abs($selisih)) }}
                        </div>
                        @if($selisih == 0)
                            <span class="badge bg-success">Seimbang</span>
                        @else
                            <span class="badge bg-danger">Tidak Seimbang</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:2.5rem;"></th>
                    <th>No. Jurnal</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Sumber</th>
                    <th class="text-end">Total Debit (Rp)</th>
                    <th class="text-end">Total Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr class="jurnal-row" role="button" data-bs-toggle="collapse" data-bs-target="#detail-{{ $item->id }}" aria-expanded="false" aria-controls="detail-{{ $item->id }}">
                    <td class="text-center">
                        <i class="bi bi-chevron-right jurnal-chevron text-muted"></i>
                    </td>
                    <td><code>{{ $item->nomor_jurnal }}</code></td>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $item->keterangan }}</td>
                    <td>
                        @if($item->sumber == 'penjualan_bbm')
                            <span class="badge bg-success">Penjualan BBM</span>
                        @elseif($item->sumber == 'pengeluaran')
                            <span class="badge bg-danger">Pengeluaran</span>
                        @elseif($item->sumber == 'payroll')
                            <span class="badge bg-primary">Gaji</span>
                        @elseif($item->sumber == 'pembelian_bbm')
                            <span class="badge bg-info text-dark">Pembelian BBM</span>
                        @else
                            <span class="badge bg-secondary">Manual</span>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($item->total_debit) }}</td>
                    <td class="text-end">{{ number_format($item->total_kredit) }}</td>
                </tr>
                <tr class="collapse" id="detail-{{ $item->id }}" data-jurnal-row-id="{{ $item->id }}">
                    <td colspan="7" class="p-0 border-0">
                        <div class="bg-light p-3">
                            @if($item->sumber == 'payroll' && $item->referensi_id)
                                <div class="mb-2">
                                    <a href="{{ route('penggajian.show', $item->referensi_id) }}">
                                        <i class="bi bi-cash-stack me-1"></i>Lihat Payroll Terkait
                                    </a>
                                </div>
                            @endif
                            <table class="table table-sm bg-white mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode Akun</th>
                                        <th>Nama Akun</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Debit (Rp)</th>
                                        <th class="text-end">Kredit (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item->details as $detail)
                                    <tr>
                                        <td><code>{{ $detail->coa->kode_akun }}</code></td>
                                        <td>{{ $detail->coa->nama_akun }}</td>
                                        <td class="text-muted small">{{ $detail->keterangan }}</td>
                                        <td class="text-end">
                                            {{ $detail->posisi == 'debit' ? number_format($detail->jumlah) : '-' }}
                                        </td>
                                        <td class="text-end">
                                            {{ $detail->posisi == 'kredit' ? number_format($detail->jumlah) : '-' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total</th>
                                        <th class="text-end">{{ number_format($item->total_debit) }}</th>
                                        <th class="text-end">{{ number_format($item->total_kredit) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
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

@push('styles')
<style>
    .jurnal-row[aria-expanded="true"] .jurnal-chevron { transform: rotate(90deg); }
    .jurnal-chevron { transition: transform 0.15s ease; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.jurnal-row').forEach(function (row) {
        var targetId = row.getAttribute('data-bs-target');
        var target = document.querySelector(targetId);
        if (!target) return;
        target.addEventListener('show.bs.collapse', function () { row.setAttribute('aria-expanded', 'true'); });
        target.addEventListener('hide.bs.collapse', function () { row.setAttribute('aria-expanded', 'false'); });
    });

    if (window.location.hash.startsWith('#jurnal-')) {
        var jurnalId = window.location.hash.replace('#jurnal-', '');
        var detailRow = document.getElementById('detail-' + jurnalId);
        if (detailRow) {
            var collapse = bootstrap.Collapse.getOrCreateInstance(detailRow, { toggle: false });
            collapse.show();
            detailRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
</script>
@endpush
@endsection