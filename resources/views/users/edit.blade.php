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
                <select name="role" class="form-select" required>
                    <option value="it"       {{ $user->role == 'it'       ? 'selected' : '' }}>IT</option>
                    <option value="manager"  {{ $user->role == 'manager'  ? 'selected' : '' }}>Manager</option>
                    <option value="pengawas" {{ $user->role == 'pengawas' ? 'selected' : '' }}>Pengawas</option>
                </select>
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
@endsection