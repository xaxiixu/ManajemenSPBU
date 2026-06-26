@extends('layouts.app')
@section('title', 'Data Petugas')
@section('page-title', 'Data Petugas')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-person-badge-fill me-2 text-danger"></i>Data Petugas</h2>
        <p>Kelola data karyawan dan operator SPBU</p>
    </div>
    @if(auth()->user()->role === 'it')
    <a href="{{ route('petugas.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah Petugas
    </a>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Jabatan</th>
                    <th>Shift Default</th>
                    <th>No. HP</th>
                    <th>Status</th>
                    @if(auth()->user()->role === 'it')
                    <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td><strong>{{ $item->nama }}</strong></td>
                    <td>{{ $item->nik ?? '-' }}</td>
                    <td><span class="badge bg-secondary text-capitalize">{{ $item->jabatan }}</span></td>
                    <td>{{ $item->shift_default ?? '-' }}</td>
                    <td>{{ $item->no_hp ?? '-' }}</td>
                    <td>
                        @if($item->is_aktif)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Nonaktif</span>
                        @endif
                    </td>
                    @if(auth()->user()->role === 'it')
                    <td>
                        <a href="{{ route('petugas.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $item->id }}, '{{ $item->nama }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->role === 'it' ? 7 : 6 }}"
                        class="text-center py-4 text-muted">
                        Belum ada data petugas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(auth()->user()->role === 'it')
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
                <h6 class="mb-1">Hapus petugas ini?</h6>
                <p class="text-muted mb-0">Petugas <strong id="namaPetugas"></strong> akan dihapus permanen.</p>
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
    document.getElementById('namaPetugas').textContent = nama;
    document.getElementById('formHapus').action = '/petugas/' + id;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
@endpush