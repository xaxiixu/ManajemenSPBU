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
                <select name="role" class="form-select" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="it"       {{ old('role') == 'it'       ? 'selected' : '' }}>IT</option>
                    <option value="manager"  {{ old('role') == 'manager'  ? 'selected' : '' }}>Manager</option>
                    <option value="pengawas" {{ old('role') == 'pengawas' ? 'selected' : '' }}>Pengawas</option>
                </select>
                @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
@endsection