@extends('layouts.app')
@section('title', 'Laporan Laba & Rugi')
@section('page-title', 'Laporan Laba & Rugi')

@section('content')

@php
    // Format Rupiah: negatif ditampilkan "-Rp 225.000" (bukan "Rp -225.000")
    $fmtRp = fn($v) => ($v < 0 ? '-Rp ' . number_format(abs($v)) : 'Rp ' . number_format($v));
    $periodeLabel = \Carbon\Carbon::parse($bulan)->locale('id')->translatedFormat('F Y');
@endphp

{{-- Filter --}}
<div class="card mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Pilih Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
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
    </div>
</div>

{{-- Kop Laporan --}}
<div class="laporan-kop">
    <div class="kop-perusahaan">PT. Berkah Membangun Usaha</div>
    <div class="kop-judul">Laporan Laba Rugi</div>
    <div class="kop-periode">Periode {{ $periodeLabel }}</div>
</div>

{{-- Tabel Laporan --}}
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-laporan-lr">
                <colgroup>
                    <col style="width:15%">
                    <col style="width:55%">
                    <col style="width:30%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Kode Akun</th>
                        <th>Nama Akun</th>
                        <th class="nominal">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- PENDAPATAN --}}
                    <tr class="section-header"><td colspan="3">Pendapatan</td></tr>
                    @forelse($pendapatan->where('total', '>', 0) as $item)
                    <tr>
                        <td>{{ $item->kode_akun }}</td>
                        <td>{{ $item->nama_akun }}</td>
                        <td class="nominal">Rp {{ number_format($item->total) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted">Belum ada pendapatan periode ini</td></tr>
                    @endforelse
                    <tr class="subtotal-row">
                        <td colspan="2">Total Pendapatan</td>
                        <td class="nominal">Rp {{ number_format($totalPendapatan) }}</td>
                    </tr>

                    {{-- HPP --}}
                    <tr class="section-header"><td colspan="3">Harga Pokok Penjualan (HPP)</td></tr>
                    @forelse($hpp->where('total', '>', 0) as $item)
                    <tr>
                        <td>{{ $item->kode_akun }}</td>
                        <td>{{ $item->nama_akun }}</td>
                        <td class="nominal">Rp {{ number_format($item->total) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted">Belum ada HPP periode ini</td></tr>
                    @endforelse
                    <tr class="subtotal-row">
                        <td colspan="2">Total HPP</td>
                        <td class="nominal">(Rp {{ number_format($totalHpp) }})</td>
                    </tr>

                    {{-- LABA KOTOR --}}
                    <tr class="subtotal-row">
                        <td colspan="2">Laba Kotor <span class="subtotal-note">(Total Pendapatan &minus; Total HPP)</span></td>
                        <td class="nominal">{{ $fmtRp($labaKotor) }}</td>
                    </tr>

                    {{-- BEBAN OPERASIONAL --}}
                    <tr class="section-header"><td colspan="3">Beban Operasional</td></tr>
                    @forelse($bebanOperasional->where('total', '>', 0) as $item)
                    <tr>
                        <td>{{ $item->kode_akun }}</td>
                        <td>{{ $item->nama_akun }}</td>
                        <td class="nominal">Rp {{ number_format($item->total) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted">Belum ada beban operasional periode ini</td></tr>
                    @endforelse
                    <tr class="subtotal-row">
                        <td colspan="2">Total Beban Operasional</td>
                        <td class="nominal">(Rp {{ number_format($totalBebanOperasional) }})</td>
                    </tr>

                    {{-- LABA/RUGI BERSIH --}}
                    <tr class="laba-bersih-row">
                        <td colspan="2">{{ $labaBersih >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }} <span class="subtotal-note">(Laba Kotor &minus; Total Beban Operasional)</span></td>
                        <td class="nominal">{{ $fmtRp($labaBersih) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ── Kop Laporan ───────────────────────────────────── */
.laporan-kop {
    border: 2px solid var(--spbu-red);
    background: var(--spbu-red-lt);
    border-radius: 10px;
    padding: 1.1rem 1rem;
    text-align: center;
    margin-bottom: 1.5rem;
}

.laporan-kop .kop-perusahaan {
    font-size: 1.05rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #1a1a2e;
    margin-bottom: .15rem;
}

.laporan-kop .kop-judul {
    font-size: 1.3rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--spbu-red-dk);
    margin-bottom: .2rem;
}

.laporan-kop .kop-periode {
    font-size: .9rem;
    font-weight: 600;
    color: #555;
}

/* ── Tabel Laporan Laba Rugi ──────────────────────────── */
.table-laporan-lr {
    width: 100%;
    border-collapse: collapse;
    font-size: .875rem;
}

.table-laporan-lr th,
.table-laporan-lr td {
    border: 1px solid #d7d7d7;
    padding: .65rem .9rem;
    vertical-align: middle;
}

.table-laporan-lr thead th {
    background: #1a1a2e;
    color: #fff;
    font-weight: 700;
    text-transform: uppercase;
    font-size: .78rem;
    letter-spacing: .04em;
    text-align: left;
}

.table-laporan-lr td.nominal,
.table-laporan-lr th.nominal {
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-feature-settings: "tnum";
}

.table-laporan-lr tbody tr.section-header td {
    background: var(--spbu-red-lt);
    color: var(--spbu-red-dk);
    font-weight: 700;
    text-transform: uppercase;
    font-size: .8rem;
    letter-spacing: .04em;
}

.table-laporan-lr tbody tr.subtotal-row td {
    background: #f7ebea;
    color: #222;
    font-weight: 700;
}

.table-laporan-lr .subtotal-note {
    font-weight: 400;
    color: #777;
    font-size: .78rem;
}

.table-laporan-lr tbody tr.laba-bersih-row td {
    background: var(--spbu-red);
    color: #fff;
    font-weight: 800;
    font-size: 1.05rem;
    border-top: 3px solid var(--spbu-red-dk);
    padding-top: .9rem;
    padding-bottom: .9rem;
}

.table-laporan-lr tbody tr.laba-bersih-row .subtotal-note {
    color: rgba(255,255,255,.75);
}

@media (max-width: 575.98px) {
    .table-laporan-lr { font-size: .8rem; }
    .table-laporan-lr th, .table-laporan-lr td { padding: .5rem .6rem; }
}

/* ── Print ─────────────────────────────────────────── */
@media print {
    #sidebar, #topbar, .d-print-none { display: none !important; }
    #main-content { margin: 0 !important; padding: 1rem !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    .laporan-kop { break-inside: avoid; }
    .table-laporan-lr thead { display: table-header-group; }
    .table-laporan-lr tr { break-inside: avoid; }
}
</style>
@endpush