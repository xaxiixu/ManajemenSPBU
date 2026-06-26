@extends('layouts.app')
@section('title', 'Tambah Presensi')
@section('page-title', 'Tambah Presensi')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-plus-circle me-2 text-danger"></i>Tambah Presensi</h2>
    <p>Input kehadiran petugas</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('presensi.store') }}" method="POST">
        @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Petugas</label>
                <select name="petugas_id" class="form-select" required>
                    <option value="">-- Pilih Petugas --</option>
                    @foreach($petugas as $p)
                    <option value="{{ $p->id }}" {{ old('petugas_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->nama }} ({{ $p->jabatan }})
                    </option>
                    @endforeach
                </select>
                @error('petugas_id')
                <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" class="form-control"
                    value="{{ old('tanggal', date('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Shift</label>
                <select name="shift" class="form-select" required>
                    <option value="">-- Pilih Shift --</option>
                    <option value="Pagi"  {{ old('shift') == 'Pagi'  ? 'selected' : '' }}>Pagi (06:00-14:00)</option>
                    <option value="Siang" {{ old('shift') == 'Siang' ? 'selected' : '' }}>Siang (14:00-22:00)</option>
                    <option value="Malam" {{ old('shift') == 'Malam' ? 'selected' : '' }}>Malam (22:00-06:00)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Status Kehadiran</label>
                <select name="status_hadir" class="form-select" required>
                    <option value="hadir"       {{ old('status_hadir') == 'hadir'       ? 'selected' : '' }}>Hadir</option>
                    <option value="sakit"       {{ old('status_hadir') == 'sakit'       ? 'selected' : '' }}>Sakit</option>
                    <option value="izin"        {{ old('status_hadir') == 'izin'        ? 'selected' : '' }}>Izin</option>
                    <option value="tidak_hadir" {{ old('status_hadir') == 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                </select>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label fw-semibold">Jam Masuk <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="time" name="jam_masuk" class="form-control" value="{{ old('jam_masuk') }}">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label fw-semibold">Jam Keluar <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="time" name="jam_keluar" class="form-control" value="{{ old('jam_keluar') }}">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="keterangan" class="form-control"
                    placeholder="cth: Terlambat 30 menit"
                    value="{{ old('keterangan') }}">
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('presensi.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection