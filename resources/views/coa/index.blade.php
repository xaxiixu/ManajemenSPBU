@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-list-columns-reverse me-2 text-danger"></i>Chart of Accounts</h2>
        <p>Daftar akun keuangan SPBU</p>
    </div>
    @if(in_array(auth()->user()->role, ['it', 'manager']))
    <a href="{{ route('coa.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah Akun
    </a>
    @endif
</div>

@php
$kategoriLabel = [
    'aset'       => ['label' => 'Aset',       'color' => 'primary'],
    'kewajiban'  => ['label' => 'Kewajiban',  'color' => 'warning'],
    'modal'      => ['label' => 'Modal',      'color' => 'info'],
    'pendapatan' => ['label' => 'Pendapatan', 'color' => 'success'],
    'beban'      => ['label' => 'Beban',      'color' => 'danger'],
];
$canEdit = in_array(auth()->user()->role, ['it', 'manager']);
@endphp

@foreach($data as $kategori => $akuns)
<div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2">
        <span class="badge bg-{{ $kategoriLabel[$kategori]['color'] ?? 'secondary' }}">
            {{ strtoupper($kategoriLabel[$kategori]['label'] ?? $kategori) }}
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="table-layout: fixed;">
            <thead class="table-light">
                <tr>
                    <th style="width:15%;">Kode Akun</th>
                    <th style="width:25%;">Nama Akun</th>
                    <th style="width:15%;">Posisi Normal</th>
                    <th style="width:30%;">Deskripsi</th>
                    <th style="width:15%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($akuns as $item)
                {{-- PARENT / STANDALONE --}}
                <tr>
                    <td>
                        @if($item->children->count() > 0)
                        <button class="btn btn-sm btn-link p-0 me-1 text-decoration-none toggle-child"
                            data-target="child-{{ $item->id }}" style="width:20px;">
                            <i class="bi bi-chevron-right toggle-icon"></i>
                        </button>
                        @else
                        <span style="display:inline-block; width:20px;"></span>
                        @endif
                        <code>{{ $item->kode_akun }}</code>
                    </td>
                    <td class="fw-semibold">{{ $item->nama_akun }}</td>
                    <td>
                        <span class="badge bg-{{ $item->posisi_normal == 'debit' ? 'primary' : 'success' }}">
                            {{ ucfirst($item->posisi_normal) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $item->deskripsi ?? '-' }}</td>
                    <td>
                        @if($canEdit)
                        <a href="{{ route('coa.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $item->id }}, '{{ $item->nama_akun }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </td>
                </tr>

                {{-- CHILD (hidden by default) --}}
                @foreach($item->children as $child)
                <tr class="child-row child-{{ $item->id }}" style="display:none; background:#fafafa;">
                    <td>
                        <span style="display:inline-block; width:20px;"></span>
                        <i class="bi bi-arrow-return-right text-muted me-1"></i>
                        <code>{{ $child->kode_akun }}</code>
                    </td>
                    <td class="ps-4">{{ $child->nama_akun }}</td>
                    <td>
                        <span class="badge bg-{{ $child->posisi_normal == 'debit' ? 'primary' : 'success' }}">
                            {{ ucfirst($child->posisi_normal) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $child->deskripsi ?? '-' }}</td>
                    <td>
                        @if($canEdit)
                        <a href="{{ route('coa.edit', $child) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $child->id }}, '{{ $child->nama_akun }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

{{-- Modal Hapus --}}
@if($canEdit)
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
                <h6 class="mb-1">Hapus akun ini?</h6>
                <p class="text-muted mb-0">Akun <strong id="namaAkun"></strong> akan dihapus permanen.</p>
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
// Toggle expand/collapse child
document.querySelectorAll('.toggle-child').forEach(btn => {
    btn.addEventListener('click', function () {
        const target = this.dataset.target;
        const rows   = document.querySelectorAll('.' + target);
        const icon   = this.querySelector('.toggle-icon');

        rows.forEach(row => {
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        });

        // Rotate icon
        icon.classList.toggle('bi-chevron-right');
        icon.classList.toggle('bi-chevron-down');
    });
});

function confirmDelete(id, nama) {
    document.getElementById('namaAkun').textContent = nama;
    document.getElementById('formHapus').action = '/coa/' + id;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
@endpush