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

{{-- Summary Cards --}}
<div class="row g-3 mb-4 d-print-none">
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid #27ae60;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:#d4edda;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-graph-up-arrow text-success fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Pendapatan</div>
                        <div class="fw-bold fs-5 text-success">Rp {{ number_format($totalPendapatan) }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            {{ $periodeLabel }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid #e74c3c;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:#f8d7da;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-graph-down-arrow text-danger fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Beban</div>
                        <div class="fw-bold fs-5 text-danger">Rp {{ number_format($totalBeban) }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            {{ $periodeLabel }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid {{ $labaBersih >= 0 ? '#27ae60' : '#e74c3c' }};">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:{{ $labaBersih >= 0 ? '#d4edda' : '#f8d7da' }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-{{ $labaBersih >= 0 ? 'emoji-smile' : 'emoji-frown' }} {{ $labaBersih >= 0 ? 'text-success' : 'text-danger' }} fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ $labaBersih >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</div>
                        <div class="fw-bold fs-5 {{ $labaBersih >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format(abs($labaBersih)) }}
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            {{ $totalPendapatan > 0 ? number_format(($labaBersih / $totalPendapatan) * 100, 1) : 0 }}% margin
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Grafik Utama & Pie Chart --}}
<div class="row g-3 mb-4 d-print-none">

    {{-- Bar Chart 6 Bulan --}}
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Tren 6 Bulan Terakhir</span>
                <small class="text-muted">Pendapatan vs Beban vs Laba</small>
            </div>
            <div class="card-body">
                <canvas id="grafikTren" height="120"></canvas>
            </div>
        </div>
    </div>

    {{-- Pie Charts --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="pieTab">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" onclick="switchPie('pendapatan', this)">Pendapatan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="switchPie('beban', this)">Beban</a>
                    </li>
                </ul>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="grafikPie" height="220"></canvas>
            </div>
        </div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ── Data dari Laravel ──────────────────────────────────
const tren     = {!! json_encode($tren) !!};
const pieData  = {!! json_encode($pieData) !!};

// ── Format Rupiah ──────────────────────────────────────
const formatRp = val => 'Rp ' + val.toLocaleString('id-ID');

// ── Bar Chart Tren 6 Bulan ─────────────────────────────
const ctxTren = document.getElementById('grafikTren').getContext('2d');
new Chart(ctxTren, {
    type: 'bar',
    data: {
        labels: tren.labels,
        datasets: [
            {
                label: 'Pendapatan',
                data: tren.pendapatan,
                backgroundColor: 'rgba(39, 174, 96, 0.8)',
                borderColor: '#27ae60',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Beban',
                data: tren.beban,
                backgroundColor: 'rgba(231, 76, 60, 0.8)',
                borderColor: '#e74c3c',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Laba/Rugi',
                data: tren.laba,
                backgroundColor: 'rgba(52, 152, 219, 0.8)',
                borderColor: '#3498db',
                borderWidth: 1,
                borderRadius: 4,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': ' + formatRp(ctx.raw)
                }
            }
        },
        scales: {
            y: {
                ticks: { callback: val => 'Rp ' + (val/1000000).toFixed(1) + 'jt' }
            }
        }
    }
});

// ── Pie Chart ──────────────────────────────────────────
const ctxPie = document.getElementById('grafikPie').getContext('2d');
let pieChart = new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: pieData.pendapatan.labels,
        datasets: [{
            data: pieData.pendapatan.values,
            backgroundColor: [
                '#27ae60', '#2ecc71', '#1abc9c',
                '#16a085', '#f39c12', '#e67e22'
            ],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.label + ': ' + formatRp(ctx.raw)
                }
            }
        }
    }
});

// ── Switch Pie Tab ─────────────────────────────────────
function switchPie(type, el) {
    event.preventDefault();
    document.querySelectorAll('#pieTab .nav-link').forEach(e => e.classList.remove('active'));
    el.classList.add('active');

    const d = type === 'pendapatan' ? pieData.pendapatan : pieData.beban;
    const colors = type === 'pendapatan'
        ? ['#27ae60','#2ecc71','#1abc9c','#16a085','#f39c12','#e67e22']
        : ['#e74c3c','#c0392b','#e67e22','#d35400','#8e44ad','#7f8c8d'];

    pieChart.data.labels = d.labels;
    pieChart.data.datasets[0].data = d.values;
    pieChart.data.datasets[0].backgroundColor = colors;
    pieChart.update();
}
</script>
@endpush