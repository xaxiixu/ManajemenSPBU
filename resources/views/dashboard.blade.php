@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@if (!empty($reminderPayroll))
<div class="alert {{ $reminderPayroll['overdue'] ? 'alert-danger' : 'alert-warning' }} d-flex align-items-center gap-3 shadow-sm">
    <i class="bi bi-cash-coin fs-3"></i>
    <div class="flex-grow-1">
        <div class="fw-bold">
            {{ $reminderPayroll['overdue'] ? 'Penggajian terlewat — segera diproses!' : 'Jadwal penggajian sudah dekat!' }}
        </div>
        <div class="small">
            Periode <strong>{{ $reminderPayroll['mulai']->translatedFormat('d M Y') }} — {{ $reminderPayroll['selesai']->translatedFormat('d M Y') }}</strong>
            belum dikirim. Segera proses payroll periode ini.
        </div>
    </div>
    <a href="{{ route('penggajian.index') }}" class="btn btn-sm {{ $reminderPayroll['overdue'] ? 'btn-danger' : 'btn-warning' }} text-nowrap">
        <i class="bi bi-arrow-right-circle me-1"></i>Proses Sekarang
    </a>
</div>
@endif

@if ($tanggalOperasional['lintas_tengah_malam'])
<div class="alert alert-warning">
    <i class="bi bi-moon-stars-fill me-1"></i>
    Masih dalam Shift {{ $tanggalOperasional['shift_aktif'] }} yang dimulai kemarin — statistik "hari ini" di bawah
    ini merujuk ke tanggal <strong>{{ $tanggalOperasional['tanggal']->format('d/m/Y') }}</strong>, bukan tanggal kalender sekarang.
</div>
@endif

{{-- Stat Cards Hari Ini --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fdf0ef;">
                <i class="bi bi-fuel-pump-fill text-danger"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">
                    Rp {{ number_format($penjualanHariIni, 0, ',', '.') }}
                </div>
                <div class="stat-label">Penjualan Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fdf0ef;">
                <i class="bi bi-cash-stack text-danger"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">
                    Rp {{ number_format($pengeluaranHariIni, 0, ',', '.') }}
                </div>
                <div class="stat-label">Pengeluaran Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:{{ $labaHariIni >= 0 ? '#d4edda' : '#f8d7da' }};">
                <i class="bi bi-graph-up-arrow {{ $labaHariIni >= 0 ? 'text-success' : 'text-danger' }}"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">
                    Rp {{ number_format(abs($labaHariIni), 0, ',', '.') }}
                </div>
                <div class="stat-label">{{ $labaHariIni >= 0 ? 'Laba' : 'Rugi' }} Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#cce5ff;">
                <i class="bi bi-person-check-fill text-primary"></i>
            </div>
            <div>
                <div class="stat-value">{{ $petugasHadir }}</div>
                <div class="stat-label">Petugas Hadir</div>
            </div>
        </div>
    </div>
</div>

{{-- Grafik & Per Jenis --}}
<div class="row g-3 mb-4">

    {{-- Grafik 7 Hari --}}
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header">Penjualan 7 Hari Terakhir</div>
            <div class="card-body">
                <canvas id="grafikPenjualan" height="100"></canvas>
            </div>
        </div>
    </div>

    {{-- Per Jenis BBM --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Penjualan per Jenis BBM (Bulan Ini)</div>
            <div class="card-body">
                @forelse($perJenis as $item)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold">{{ $item->jenis_bbm }}</span>
                        <span class="text-muted small">{{ number_format($item->liter) }} L</span>
                    </div>
                    <div class="fw-semibold text-danger">Rp {{ number_format($item->total) }}</div>
                </div>
                @empty
                <p class="text-muted text-center mt-4">Belum ada data bulan ini</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Ringkasan Bulan Ini --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #28a745;">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Pendapatan Bulan Ini</div>
                <div class="fw-bold fs-5 text-success">Rp {{ number_format($penjualanBulanIni) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #dc3545;">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Pengeluaran Bulan Ini</div>
                <div class="fw-bold fs-5 text-danger">Rp {{ number_format($pengeluaranBulanIni) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="border-left: 4px solid {{ $labaBulanIni >= 0 ? '#28a745' : '#dc3545' }};">
            <div class="card-body">
                <div class="text-muted small mb-1">{{ $labaBulanIni >= 0 ? 'Laba' : 'Rugi' }} Bulan Ini</div>
                <div class="fw-bold fs-5 {{ $labaBulanIni >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format(abs($labaBulanIni)) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Transaksi Terbaru --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Penjualan Terbaru</span>
        <a href="{{ route('penjualan-bbm.index') }}" class="btn btn-sm btn-outline-danger">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Shift</th>
                    <th>Jenis BBM</th>
                    <th>Liter</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksiTerbaru as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td><span class="badge bg-secondary">{{ $item->shift }}</span></td>
                    <td>{{ $item->jenis_bbm }}</td>
                    <td>{{ number_format($item->liter_terjual) }} L</td>
                    <td><strong>Rp {{ number_format($item->total_penjualan) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('grafikPenjualan').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($grafikLabels) !!},
        datasets: [{
            label: 'Total Penjualan (Rp)',
            data: {!! json_encode($grafikData) !!},
            backgroundColor: 'rgba(192, 57, 43, 0.8)',
            borderColor: '#c0392b',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: val => 'Rp ' + val.toLocaleString('id-ID')
                }
            }
        }
    }
});
setTimeout(() => location.reload(), 10000);
</script>
@endpush