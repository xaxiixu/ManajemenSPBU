@extends('layouts.app')
@section('title', 'Edit Presensi')
@section('page-title', 'Edit Presensi')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit Presensi</h2>
    <p>{{ $presensi->petugas->nama }} — {{ $presensi->tanggal->format('d/m/Y') }} Shift {{ $presensi->shift }}</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('presensi.update', $presensi) }}" method="POST">
        @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Petugas</label>
                <input type="text" class="form-control" value="{{ $presensi->petugas->nama }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Tanggal & Shift</label>
                <input type="text" class="form-control"
                    value="{{ $presensi->tanggal->format('d/m/Y') }} — Shift {{ $presensi->shift }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Status Kehadiran</label>
                <select name="status_hadir" class="form-select" required>
                    <option value="hadir"       {{ $presensi->status_hadir == 'hadir'       ? 'selected' : '' }}>Hadir</option>
                    <option value="sakit"       {{ $presensi->status_hadir == 'sakit'       ? 'selected' : '' }}>Sakit</option>
                    <option value="izin"        {{ $presensi->status_hadir == 'izin'        ? 'selected' : '' }}>Izin</option>
                    <option value="tidak_hadir" {{ $presensi->status_hadir == 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                </select>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label fw-semibold">Jam Masuk</label>
                    <input type="time" name="jam_masuk" class="form-control" value="{{ $presensi->jam_masuk }}">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label fw-semibold">Jam Keluar</label>
                    <input type="time" name="jam_keluar" class="form-control" value="{{ $presensi->jam_keluar }}">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Keterangan</label>
                <input type="text" name="keterangan" class="form-control" value="{{ $presensi->keterangan }}">
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('presensi.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-save me-1"></i>Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection