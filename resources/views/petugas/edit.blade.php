@extends('layouts.app')
@section('title', 'Edit Petugas')
@section('page-title', 'Edit Petugas')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit Petugas</h2>
    <p>Update data {{ $petugas->nama }}</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('petugas.update', $petugas) }}" method="POST">
        @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="{{ old('nama', $petugas->nama) }}" required>
                @error('nama')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">NIK</label>
                <input type="text" name="nik" class="form-control" value="{{ old('nik', $petugas->nik) }}">
                @error('nik')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Jabatan</label>
                <select name="jabatan" class="form-select" required>
                    <option value="operator"   {{ $petugas->jabatan == 'operator'   ? 'selected' : '' }}>Operator</option>
                    <option value="supervisor" {{ $petugas->jabatan == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                    <option value="kasir"      {{ $petugas->jabatan == 'kasir'      ? 'selected' : '' }}>Kasir</option>
                    <option value="teknisi"    {{ $petugas->jabatan == 'teknisi'    ? 'selected' : '' }}>Teknisi</option>
                    <option value="lainnya"    {{ $petugas->jabatan == 'lainnya'    ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Shift Default</label>
                <select name="shift_default" class="form-select">
                    <option value="">-- Tidak ada --</option>
                    <option value="Pagi"  {{ $petugas->shift_default == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                    <option value="Siang" {{ $petugas->shift_default == 'Siang' ? 'selected' : '' }}>Siang</option>
                    <option value="Malam" {{ $petugas->shift_default == 'Malam' ? 'selected' : '' }}>Malam</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">No. HP</label>
                <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $petugas->no_hp) }}">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select name="is_aktif" class="form-select" required>
                    <option value="1" {{ $petugas->is_aktif ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ !$petugas->is_aktif ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('petugas.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Update</button>
            </div>
        </form>
    </div>
</div>
@endsection