@extends('layouts.app')
@section('title', 'Laporan Laba & Rugi')
@section('page-title', 'Laporan Laba & Rugi')

@section('content')

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

{{-- Header Print --}}
<div class="d-none d-print-block text-center mb-4">
    <h4 class="mb-0 fw-bold">SPBU Management System</h4>
    <h5>Laporan Laba & Rugi</h5>
    <p class="mb-0 text-muted">Periode: {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}</p>
    <hr>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
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
                            {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
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
                            {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100" style="border-left: 4px solid {{ $labaRugi >= 0 ? '#27ae60' : '#e74c3c' }};">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:{{ $labaRugi >= 0 ? '#d4edda' : '#f8d7da' }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-{{ $labaRugi >= 0 ? 'emoji-smile' : 'emoji-frown' }} {{ $labaRugi >= 0 ? 'text-success' : 'text-danger' }} fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">{{ $labaRugi >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</div>
                        <div class="fw-bold fs-5 {{ $labaRugi >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format(abs($labaRugi)) }}
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            {{ $totalPendapatan > 0 ? number_format(($labaRugi / $totalPendapatan) * 100, 1) : 0 }}% margin
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

{{-- Tabel Rincian --}}
<div class="card">
    <div class="card-body">

        {{-- PENDAPATAN --}}
        <h6 class="fw-bold text-uppercase mb-3" style="letter-spacing:.05em; color:#27ae60;">
            <i class="bi bi-arrow-up-circle-fill me-2"></i>Pendapatan
        </h6>
        <table class="table table-borderless mb-0">
            @forelse($pendapatan->where('total', '>', 0) as $item)
            <tr>
                <td class="ps-3" width="40%">{{ $item->nama_akun }}</td>
                <td width="30%">
                    <div class="progress" style="height:6px; border-radius:3px;">
                        <div class="progress-bar bg-success" style="width:{{ $totalPendapatan > 0 ? ($item->total / $totalPendapatan) * 100 : 0 }}%"></div>
                    </div>
                </td>
                <td class="text-end" width="15%">
                    <small class="text-muted">{{ $totalPendapatan > 0 ? number_format(($item->total / $totalPendapatan) * 100, 1) : 0 }}%</small>
                </td>
                <td class="text-end fw-semibold" width="15%">Rp {{ number_format($item->total) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted ps-3">Belum ada pendapatan periode ini</td></tr>
            @endforelse
            <tr class="border-top">
                <td class="ps-3 fw-bold">Total Pendapatan</td>
                <td colspan="2"></td>
                <td class="text-end fw-bold text-success">Rp {{ number_format($totalPendapatan) }}</td>
            </tr>
        </table>

        <hr class="my-4">

        {{-- BEBAN --}}
        <h6 class="fw-bold text-uppercase mb-3" style="letter-spacing:.05em; color:#e74c3c;">
            <i class="bi bi-arrow-down-circle-fill me-2"></i>Beban Operasional
        </h6>
        <table class="table table-borderless mb-0">
            @forelse($beban->where('total', '>', 0) as $item)
            <tr>
                <td class="ps-3" width="40%">{{ $item->nama_akun }}</td>
                <td width="30%">
                    <div class="progress" style="height:6px; border-radius:3px;">
                        <div class="progress-bar bg-danger" style="width:{{ $totalBeban > 0 ? ($item->total / $totalBeban) * 100 : 0 }}%"></div>
                    </div>
                </td>
                <td class="text-end" width="15%">
                    <small class="text-muted">{{ $totalBeban > 0 ? number_format(($item->total / $totalBeban) * 100, 1) : 0 }}%</small>
                </td>
                <td class="text-end fw-semibold" width="15%">Rp {{ number_format($item->total) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted ps-3">Belum ada beban periode ini</td></tr>
            @endforelse
            <tr class="border-top">
                <td class="ps-3 fw-bold">Total Beban</td>
                <td colspan="2"></td>
                <td class="text-end fw-bold text-danger">Rp {{ number_format($totalBeban) }}</td>
            </tr>
        </table>

        <hr class="my-4">

        {{-- LABA/RUGI --}}
        <div class="p-4 rounded-3 d-flex justify-content-between align-items-center"
            style="background: {{ $labaRugi >= 0 ? '#d4edda' : '#f8d7da' }};">
            <div>
                <div class="fw-bold fs-5 {{ $labaRugi >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $labaRugi >= 0 ? '✅ Laba Bersih' : '❌ Rugi Bersih' }}
                </div>
                <small class="text-muted">
                    Periode {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
                </small>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-3 {{ $labaRugi >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format(abs($labaRugi)) }}
                </div>
                @if($totalPendapatan > 0)
                <small class="text-muted">
                    Margin {{ number_format(($labaRugi / $totalPendapatan) * 100, 1) }}%
                </small>
                @endif
            </div>
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