@extends('layouts.app')
@section('title', 'Tambah Petugas')
@section('page-title', 'Tambah Petugas')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-person-plus me-2 text-danger"></i>Tambah Petugas</h2>
    <p>Tambah data karyawan atau operator baru</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('petugas.store') }}" method="POST">
        @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required>
                @error('nama')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">NIK <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="nik" class="form-control" value="{{ old('nik') }}">
                @error('nik')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Jabatan</label>
                <select name="jabatan" class="form-select" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="operator"   {{ old('jabatan') == 'operator'   ? 'selected' : '' }}>Operator</option>
                    <option value="supervisor" {{ old('jabatan') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                    <option value="kasir"      {{ old('jabatan') == 'kasir'      ? 'selected' : '' }}>Kasir</option>
                    <option value="teknisi"    {{ old('jabatan') == 'teknisi'    ? 'selected' : '' }}>Teknisi</option>
                    <option value="lainnya"    {{ old('jabatan') == 'lainnya'    ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Shift Default <span class="text-muted fw-normal">(opsional)</span></label>
                <select name="shift_default" class="form-select">
                    <option value="">-- Pilih Shift --</option>
                    <option value="Pagi"  {{ old('shift_default') == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                    <option value="Siang" {{ old('shift_default') == 'Siang' ? 'selected' : '' }}>Siang</option>
                    <option value="Malam" {{ old('shift_default') == 'Malam' ? 'selected' : '' }}>Malam</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">No. HP <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}">
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('petugas.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection