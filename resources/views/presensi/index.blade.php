@extends('layouts.app')
@section('title', 'Presensi')
@section('page-title', 'Presensi')

@php
$statusInfo = [
    'hadir'       => ['label' => 'Hadir',       'badge' => 'success'],
    'sakit'       => ['label' => 'Sakit',       'badge' => 'warning'],
    'izin'        => ['label' => 'Izin',        'badge' => 'info'],
    'tidak_hadir' => ['label' => 'Tidak Hadir', 'badge' => 'danger'],
];
@endphp

@section('content')
<div class="page-header">
    <h2><i class="bi bi-person-check-fill me-2 text-danger"></i>Presensi</h2>
    <p>Absen masuk & keluar shift Anda</p>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Status Hari Ini</div>
            <div class="card-body">
                @if($sesiTerbuka)
                    <p class="mb-1">
                        Anda sedang bertugas shift <strong>{{ $sesiTerbuka->shift }}</strong>
                        sejak <strong>{{ $sesiTerbuka->jam_masuk }}</strong>
                        ({{ $sesiTerbuka->tanggal->format('d/m/Y') }}).
                    </p>
                    @if($sesiTerbuka->menit_telat > 0)
                    <p class="text-warning small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Telat {{ $sesiTerbuka->menit_telat }} menit.
                    </p>
                    @endif
                    <form method="POST" action="{{ route('presensi.absen-keluar') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right me-1"></i>Absen Keluar
                        </button>
                    </form>
                @elseif($absenHariIni)
                    <p class="mb-0">
                        Presensi hari ini sudah tercatat:
                        <span class="badge bg-{{ $statusInfo[$absenHariIni->status_hadir]['badge'] ?? 'secondary' }}">
                            {{ $statusInfo[$absenHariIni->status_hadir]['label'] ?? $absenHariIni->status_hadir }}
                        </span>
                        @if($absenHariIni->jam_masuk)
                            — masuk {{ $absenHariIni->jam_masuk }}
                            @if($absenHariIni->jam_keluar) / keluar {{ $absenHariIni->jam_keluar }} @endif
                        @endif
                    </p>
                @elseif(!auth()->user()->shift_default)
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Shift Anda belum diatur. Hubungi pengawas untuk mengatur jadwal shift Anda.
                    </div>
                @else
                    <p class="mb-3">
                        Shift Anda: <strong>{{ auth()->user()->shift_default }}</strong>
                        @if($shiftMaster)
                            ({{ substr($shiftMaster->jam_mulai, 0, 5) }}-{{ substr($shiftMaster->jam_selesai, 0, 5) }})
                        @endif
                    </p>
                    <form method="POST" action="{{ route('presensi.absen-masuk') }}">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Absen Masuk
                        </button>
                    </form>
                    <p class="text-muted small mt-2 mb-0">
                        Ingin ubah shift? Ajukan lewat
                        <a href="{{ route('pengajuan-shift.index') }}">Pengajuan Shift</a>.
                    </p>
                @endif
                @error('presensi')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Riwayat Presensi (30 terakhir)</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Shift</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status</th>
                    <th>Menit Telat</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td><span class="badge bg-secondary">{{ $item->shift }}</span></td>
                    <td>{{ $item->jam_masuk ?? '-' }}</td>
                    <td>{{ $item->jam_keluar ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ $statusInfo[$item->status_hadir]['badge'] ?? 'secondary' }}">
                            {{ $statusInfo[$item->status_hadir]['label'] ?? $item->status_hadir }}
                        </span>
                    </td>
                    <td>{{ $item->menit_telat > 0 ? $item->menit_telat . ' menit' : '-' }}</td>
                    <td>{{ $item->keterangan ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat presensi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
