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
        <form action="{{ route('users.update', $user) }}" method="POST" id="formEditUser">
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
                    <label class="form-label fw-semibold">Shift Default</label>
                    <select name="shift_default" class="form-select">
                        <option value="">-- Pilih Shift --</option>
                        <option value="Pagi"  {{ old('shift_default', $user->shift_default) == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                        <option value="Siang" {{ old('shift_default', $user->shift_default) == 'Siang' ? 'selected' : '' }}>Siang</option>
                        <option value="Malam" {{ old('shift_default', $user->shift_default) == 'Malam' ? 'selected' : '' }}>Malam</option>
                    </select>
                    @error('shift_default')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">No. HP</label>
                    <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $user->no_hp) }}">
                    @error('no_hp')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Gaji Pokok</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="gaji_pokok" class="form-control" min="0"
                            value="{{ old('gaji_pokok', $user->gaji_pokok) }}">
                        <span class="input-group-text">/ bulan</span>
                    </div>
                    <div class="form-text">Nominal bulanan tetap, dipakai dalam perhitungan payroll. Mengubah ini tidak mengubah slip periode yang sudah dikirim.</div>
                    @error('gaji_pokok')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Tanggal Bergabung</label>
                    <input type="date" name="tanggal_bergabung" class="form-control"
                        value="{{ old('tanggal_bergabung', optional($user->tanggal_bergabung)->format('Y-m-d')) }}">
                    <div class="form-text">Dipakai untuk prorate gaji petugas baru di periode pertamanya.</div>
                    @error('tanggal_bergabung')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
                <button type="submit" class="btn btn-danger" id="btnUpdateUser">
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

    document.getElementById('formEditUser').addEventListener('submit', function () {
        document.getElementById('btnUpdateUser').disabled = true;
    });
});
</script>
@endpush
@endsection