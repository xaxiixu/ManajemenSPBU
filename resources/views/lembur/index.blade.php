@extends('layouts.app')
@section('title', 'Pengajuan Lembur')
@section('page-title', 'Pengajuan Lembur')

@php
$statusInfo = [
    'pending'  => ['label' => 'Pending',  'badge' => 'warning'],
    'approved' => ['label' => 'Disetujui', 'badge' => 'success'],
    'rejected' => ['label' => 'Ditolak',  'badge' => 'danger'],
];
@endphp

@section('content')
<div class="page-header">
    <h2><i class="bi bi-clock-history me-2 text-danger"></i>Pengajuan Lembur</h2>
    <p>Ajukan lembur harian Anda</p>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Form Pengajuan</div>
            <div class="card-body">
                <form method="POST" action="{{ route('lembur.store') }}">
                @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                        @error('tanggal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" value="{{ old('jam_mulai') }}" required>
                            @error('jam_mulai')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" value="{{ old('jam_selesai') }}" required>
                            @error('jam_selesai')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan</label>
                        <textarea name="alasan" class="form-control" rows="3" required>{{ old('alasan') }}</textarea>
                        @error('alasan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-send me-1"></i>Ajukan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Riwayat Lembur Saya</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $item->jam_mulai }} - {{ $item->jam_selesai }}</td>
                            <td>{{ $item->alasan }}</td>
                            <td>
                                <span class="badge bg-{{ $statusInfo[$item->status]['badge'] }}">
                                    {{ $statusInfo[$item->status]['label'] }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $item->catatan_approval ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada pengajuan lembur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
