@extends('layouts.app')
@section('title', 'Pengeluaran Operasional')
@section('page-title', 'Pengeluaran Operasional')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-cash-stack me-2 text-danger"></i>Pengeluaran Operasional</h2>
        <p>Data pengeluaran operasional SPBU</p>
    </div>
    @if(in_array(auth()->user()->role, ['pengawas']))
    <a href="{{ route('pengeluaran.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pengeluaran
    </a>
    @endif
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control" value="{{ $dari }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control" value="{{ $sampai }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Atau Pilih Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">&nbsp;</label>
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">&nbsp;</label>
                <a href="{{ route('pengeluaran.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            </div>
        </form>
        <small class="text-muted mt-2 d-block">
            <i class="bi bi-info-circle me-1"></i>Jika filter bulan diisi, filter range tanggal akan diabaikan.
        </small>
    </div>
</div>

{{-- Total --}}
<div class="card mb-3" style="border-left: 4px solid #c0392b;">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Total Pengeluaran Periode Ini</span>
            <strong class="text-danger fs-5">Rp {{ number_format($total) }}</strong>
        </div>
    </div>
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Bukti</th>
                    @if(in_array(auth()->user()->role, ['pengawas']))
                    <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td><span class="badge bg-secondary">{{ $item->coa->nama_akun ?? '-' }}</span></td>
                    <td>{{ $item->keterangan }}</td>
                    <td><strong>Rp {{ number_format($item->jumlah) }}</strong></td>
                    <td>
                        @if($item->bukti_pembayaran)
                            <a href="{{ asset('storage/' . $item->bukti_pembayaran) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-image"></i> Lihat
                            </a>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                    @if(in_array(auth()->user()->role, ['pengawas']))
                    <td>
                        <a href="{{ route('pengeluaran.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $item->id }}, '{{ $item->keterangan }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ in_array(auth()->user()->role, ['pengawas']) ? 6 : 5 }}"
                        class="text-center py-4 text-muted">
                        Belum ada data pengeluaran untuk periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Hapus --}}
@if(in_array(auth()->user()->role, ['pengawas']))
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
                <h6 class="mb-1">Hapus pengeluaran ini?</h6>
                <p class="text-muted mb-0">Data <strong id="namaItem"></strong> akan dihapus permanen.</p>
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
    document.getElementById('namaItem').textContent = nama;
    document.getElementById('formHapus').action = '/pengeluaran/' + id;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
@endpush