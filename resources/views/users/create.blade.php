@extends('layouts.app')
@section('title', 'Tambah User')
@section('page-title', 'Tambah User')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-person-plus me-2 text-danger"></i>Tambah User</h2>
    <p>Buat akun pengguna baru</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
        @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap</label>
                <input type="text" name="name" class="form-control"
                    value="{{ old('name') }}" required>
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control"
                    value="{{ old('email') }}" required>
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Role</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="it"       {{ old('role') == 'it'       ? 'selected' : '' }}>IT</option>
                    <option value="manager"  {{ old('role') == 'manager'  ? 'selected' : '' }}>Manager</option>
                    <option value="pengawas" {{ old('role') == 'pengawas' ? 'selected' : '' }}>Pengawas</option>
                    <option value="petugas"  {{ old('role') == 'petugas'  ? 'selected' : '' }}>Petugas</option>
                </select>
                @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div id="fieldsPetugas" class="border rounded p-3 mb-3" style="display:none; background:#f8f9fa;">
                <div class="mb-3">
                    <label class="form-label fw-semibold">NIK</label>
                    <input type="text" name="nik" class="form-control" value="{{ old('nik') }}">
                    @error('nik')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jabatan</label>
                    <select name="jabatan" class="form-select">
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="operator"   {{ old('jabatan') == 'operator'   ? 'selected' : '' }}>Operator</option>
                        <option value="supervisor" {{ old('jabatan') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        <option value="kasir"      {{ old('jabatan') == 'kasir'      ? 'selected' : '' }}>Kasir</option>
                        <option value="teknisi"    {{ old('jabatan') == 'teknisi'    ? 'selected' : '' }}>Teknisi</option>
                        <option value="lainnya"    {{ old('jabatan') == 'lainnya'    ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('jabatan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Shift Default</label>
                    <select name="shift_default" class="form-select">
                        <option value="">-- Pilih Shift --</option>
                        <option value="Pagi"  {{ old('shift_default') == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                        <option value="Siang" {{ old('shift_default') == 'Siang' ? 'selected' : '' }}>Siang</option>
                        <option value="Malam" {{ old('shift_default') == 'Malam' ? 'selected' : '' }}>Malam</option>
                    </select>
                    @error('shift_default')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">No. HP</label>
                    <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}">
                    @error('no_hp')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" required>
                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleEl = document.getElementById('role');
    const fields = document.getElementById('fieldsPetugas');

    function toggle() {
        fields.style.display = roleEl.value === 'petugas' ? 'block' : 'none';
    }

    roleEl.addEventListener('change', toggle);
    toggle();
});
</script>
@endpush
@endsection