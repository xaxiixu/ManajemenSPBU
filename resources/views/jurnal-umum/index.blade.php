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
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Ref</th>
                        <th class="text-end">Debit (Rp)</th>
                        <th class="text-end">Kredit (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                        @php
                            $rowspan = $item->details->count();
                            $bulanKey = $item->tanggal->format('Y-m');
                            $nextItem = $data[$loop->index + 1] ?? null;
                            $isLastOfMonth = !$nextItem || $nextItem->tanggal->format('Y-m') !== $bulanKey;
                        @endphp
                        @foreach($item->details as $detail)
                        <tr @if($loop->first) id="jurnal-{{ $item->id }}" @endif
                            class="{{ $loop->parent->index % 2 == 0 ? 'jurnal-group-even' : 'jurnal-group-odd' }}">
                            @if($loop->first)
                            <td rowspan="{{ $rowspan }}">{{ $item->tanggal->format('d/m/Y') }}</td>
                            @endif
                            <td>
                                @if($loop->first)
                                <div class="text-muted" style="font-size:0.75rem;">
                                    <code>{{ $item->nomor_jurnal }}</code> · {{ $sumberOptions[$item->sumber] ?? 'Manual' }}
                                    @if($item->sumber == 'payroll' && $item->referensi_id)
                                        · <a href="{{ route('penggajian.show', $item->referensi_id) }}">Lihat Payroll</a>
                                    @endif
                                </div>
                                @endif
                                <div class="fw-semibold">{{ $detail->coa->nama_akun }}</div>
                                <div class="fst-italic text-muted small">{{ $detail->keterangan }}</div>
                            </td>
                            <td class="text-muted small"><code>{{ $detail->coa->kode_akun }}</code></td>
                            <td class="text-end">
                                @if($detail->posisi == 'debit')
                                    <span class="text-success">{{ number_format($detail->jumlah) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($detail->posisi == 'kredit')
                                    <span class="text-danger">{{ number_format($detail->jumlah) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @if($isLastOfMonth)
                        <tr class="jurnal-month-total">
                            <td colspan="3" class="text-end fw-semibold text-muted">
                                Total Bulan {{ $item->tanggal->clone()->locale('id')->translatedFormat('F Y') }}
                            </td>
                            <td class="text-end fw-semibold text-success">{{ number_format($totalPerBulan[$bulanKey]['debit'] ?? 0) }}</td>
                            <td class="text-end fw-semibold text-danger">{{ number_format($totalPerBulan[$bulanKey]['kredit'] ?? 0) }}</td>
                        </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            Belum ada jurnal untuk periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($data->hasPages())
<div class="mt-3">
    {{ $data->links() }}
</div>
@endif

@push('styles')
<style>
    .jurnal-group-odd { background-color: #ffffff; }
    .jurnal-group-even { background-color: #f8f9fa; }
    .jurnal-month-total td { border-bottom: 3px solid #adb5bd; background-color: #eef1f4; }
</style>
@endpush
@endsection