@extends('layouts.app')
@section('title', 'Pengajuan Shift')
@section('page-title', 'Pengajuan Shift')

@php
$statusInfo = [
    'pending'  => ['label' => 'Pending',  'badge' => 'warning'],
    'approved' => ['label' => 'Disetujui', 'badge' => 'success'],
    'rejected' => ['label' => 'Ditolak',  'badge' => 'danger'],
];
@endphp

@section('content')
<div class="page-header">
    <h2><i class="bi bi-arrow-repeat me-2 text-danger"></i>Pengajuan Shift</h2>
    <p>Ajukan perubahan shift default Anda</p>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Form Pengajuan</div>
            <div class="card-body">
                <form method="POST" action="{{ route('pengajuan-shift.store') }}">
                @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Shift Diminta</label>
                        <select name="shift_diminta" class="form-select" required>
                            <option value="">-- Pilih Shift --</option>
                            <option value="Pagi"  {{ old('shift_diminta') == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                            <option value="Siang" {{ old('shift_diminta') == 'Siang' ? 'selected' : '' }}>Siang</option>
                            <option value="Malam" {{ old('shift_diminta') == 'Malam' ? 'selected' : '' }}>Malam</option>
                        </select>
                        @error('shift_diminta')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Berlaku Mulai Tanggal</label>
                        <input type="date" name="tanggal_berlaku" class="form-control" value="{{ old('tanggal_berlaku') }}" required>
                        @error('tanggal_berlaku')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
            <div class="card-header">Riwayat Pengajuan Saya</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Shift Diminta</th>
                            <th>Berlaku</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $item->shift_diminta }}</span></td>
                            <td>{{ $item->tanggal_berlaku->format('d/m/Y') }}</td>
                            <td>{{ $item->alasan }}</td>
                            <td>
                                <span class="badge bg-{{ $statusInfo[$item->status]['badge'] }}">
                                    {{ $statusInfo[$item->status]['label'] }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $item->catatan_approval ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada pengajuan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
