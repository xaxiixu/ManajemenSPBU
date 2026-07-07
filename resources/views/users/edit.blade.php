@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit User</h2>
    <p>Update akun {{ $user->name }}</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap</label>
                <input type="text" name="name" class="form-control"
                    value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control"
                    value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Role</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="it"       {{ old('role', $user->role) == 'it'       ? 'selected' : '' }}>IT</option>
                    <option value="manager"  {{ old('role', $user->role) == 'manager'  ? 'selected' : '' }}>Manager</option>
                    <option value="pengawas" {{ old('role', $user->role) == 'pengawas' ? 'selected' : '' }}>Pengawas</option>
                    <option value="petugas"  {{ old('role', $user->role) == 'petugas'  ? 'selected' : '' }}>Petugas</option>
                </select>
                @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div id="fieldsPetugas" class="border rounded p-3 mb-3" style="display:none; background:#f8f9fa;">
                <div class="mb-3">
                    <label class="form-label fw-semibold">NIK</label>
                    <input type="text" name="nik" class="form-control" value="{{ old('nik', $user->nik) }}">
                    @error('nik')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jabatan</label>
                    <select name="jabatan" class="form-select">
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="operator"   {{ old('jabatan', $user->jabatan) == 'operator'   ? 'selected' : '' }}>Operator</option>
                        <option value="supervisor" {{ old('jabatan', $user->jabatan) == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        <option value="kasir"      {{ old('jabatan', $user->jabatan) == 'kasir'      ? 'selected' : '' }}>Kasir</option>
                        <option value="teknisi"    {{ old('jabatan', $user->jabatan) == 'teknisi'    ? 'selected' : '' }}>Teknisi</option>
                        <option value="lainnya"    {{ old('jabatan', $user->jabatan) == 'lainnya'    ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('jabatan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Shift Default</label>
                    <select name="shift_default" class="form-select">
                        <option value="">-- Pilih Shift --</option>
                        <option value="Pagi"  {{ old('shift_default', $user->shift_default) == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                        <option value="Siang" {{ old('shift_default', $user->shift_default) == 'Siang' ? 'selected' : '' }}>Siang</option>
                        <option value="Malam" {{ old('shift_default', $user->shift_default) == 'Malam' ? 'selected' : '' }}>Malam</option>
                    </select>
                    @error('shift_default')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">No. HP</label>
                    <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $user->no_hp) }}">
                    @error('no_hp')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="is_active" class="form-select" required>
                    <option value="1" {{ $user->is_active ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Password Baru <span class="text-muted fw-normal">(kosongkan jika tidak diubah)</span>
                </label>
                <input type="password" name="password" class="form-control">
                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-save me-1"></i>Update
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