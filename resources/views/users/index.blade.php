@extends('layouts.app')
@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-people-fill me-2 text-danger"></i>Manajemen User</h2>
        <p>Kelola akun pengguna sistem</p>
    </div>
    @if(auth()->user()->role === 'manager')
    <a href="{{ route('users.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah User
    </a>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    @if(auth()->user()->role === 'manager')
                    <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:34px;height:34px;background:#c0392b;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0;">
                                {{ strtoupper(substr($item->name, 0, 1)) }}
                            </div>
                            <strong>{{ $item->name }}</strong>
                            @if($item->id === auth()->id())
                                <span class="badge bg-secondary">Anda</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $item->email }}</td>
                    <td>
                        @php
                            $badgeColor = match($item->role) {
                                'manager'  => 'bg-dark',
                                'pengawas' => 'bg-danger',
                                'petugas'  => 'bg-info',
                                default    => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badgeColor }}">
                            {{ ucfirst($item->role) }}
                        </span>
                    </td>
                    <td>
                        @if($item->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Nonaktif</span>
                        @endif
                    </td>
                    @if(auth()->user()->role === 'manager')
                    <td>
                        <a href="{{ route('users.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        @if($item->id !== auth()->id())
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $item->id }}, '{{ $item->name }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->role === 'manager' ? 5 : 4 }}"
                        class="text-center py-4 text-muted">
                        Belum ada user.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(auth()->user()->role === 'manager')
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div style="width:70px;height:70px;background:#fdf0ef;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <i class="bi bi-person-x-fill text-danger" style="font-size:2rem;"></i>
                </div>
                <h6 class="mb-1">Hapus user ini?</h6>
                <p class="text-muted mb-2">User <strong id="namaUser"></strong> akan dihapus permanen.</p>
                <div class="alert alert-warning text-start small mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Riwayat presensi, pengajuan shift, dan lembur milik user ini akan tetap tersimpan
                    (ditampilkan sebagai "Petugas dihapus"), tapi akun login &amp; datanya tidak bisa dikembalikan.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                <form id="formHapus" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-trash me-1"></i>Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function confirmDelete(id, nama) {
    document.getElementById('namaUser').textContent = nama;
    document.getElementById('formHapus').action = '/users/' + id;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
@endpush