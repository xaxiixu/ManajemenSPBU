@extends('layouts.app')
@section('title', 'Master BBM')
@section('page-title', 'Master BBM')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-fuel-pump-fill me-2 text-danger"></i>Master BBM</h2>
        <p>Daftar jenis BBM, RON, dan harga per liter</p>
    </div>
    <a href="{{ route('master-bbm.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah Jenis BBM
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Jenis BBM</th>
                    <th>RON</th>
                    <th>Harga/Liter</th>
                    <th>Akun Pendapatan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td class="fw-semibold">{{ $item->jenis_bbm }}</td>
                    <td>{{ $item->ron ?? '-' }}</td>
                    <td>Rp {{ number_format($item->harga_per_liter) }}</td>
                    <td><code>{{ $item->coa->kode_akun ?? '-' }}</code> {{ $item->coa->nama_akun ?? '' }}</td>
                    <td>
                        <span class="badge {{ $item->is_aktif ? 'bg-success' : 'bg-secondary' }}">
                            {{ $item->is_aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('master-bbm.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $item->id }}, '{{ $item->jenis_bbm }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data jenis BBM.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Hapus --}}
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
                    <i class="bi bi-trash-fill text-danger" style="font-size:2rem;"></i>
                </div>
                <h6 class="mb-1">Hapus jenis BBM ini?</h6>
                <p class="text-muted mb-0">Jenis BBM <strong id="namaItem"></strong> akan dihapus permanen.</p>
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
@endsection

@push('scripts')
<script>
function confirmDelete(id, nama) {
    document.getElementById('namaItem').textContent = nama;
    document.getElementById('formHapus').action = '/master-bbm/' + id;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
@endpush
