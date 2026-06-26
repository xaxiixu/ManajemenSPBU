@extends('layouts.app')
@section('title', 'Presensi')
@section('page-title', 'Presensi')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-person-check-fill me-2 text-danger"></i>Presensi</h2>
        <p>Data kehadiran petugas shift</p>
    </div>
    @if(in_array(auth()->user()->role, ['pengawas', 'it']))
    <a href="{{ route('presensi.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah Presensi
    </a>
    @endif
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Shift</label>
                <select name="shift" class="form-select">
                    <option value="">Semua Shift</option>
                    <option value="Pagi"  {{ $shift == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                    <option value="Siang" {{ $shift == 'Siang' ? 'selected' : '' }}>Siang</option>
                    <option value="Malam" {{ $shift == 'Malam' ? 'selected' : '' }}>Malam</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('presensi.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Ringkasan --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d4edda;">
                <i class="bi bi-check-circle-fill text-success"></i>
            </div>
            <div>
                <div class="stat-value">{{ $ringkasan['hadir'] }}</div>
                <div class="stat-label">Hadir</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff3cd;">
                <i class="bi bi-thermometer-half text-warning"></i>
            </div>
            <div>
                <div class="stat-value">{{ $ringkasan['sakit'] }}</div>
                <div class="stat-label">Sakit</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#cce5ff;">
                <i class="bi bi-info-circle-fill text-info"></i>
            </div>
            <div>
                <div class="stat-value">{{ $ringkasan['izin'] }}</div>
                <div class="stat-label">Izin</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f8d7da;">
                <i class="bi bi-x-circle-fill text-danger"></i>
            </div>
            <div>
                <div class="stat-value">{{ $ringkasan['tidak_hadir'] }}</div>
                <div class="stat-label">Tidak Hadir</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama Petugas</th>
                    <th>Tanggal</th>
                    <th>Shift</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    @if(in_array(auth()->user()->role, ['pengawas', 'it']))
                    <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td><strong>{{ $item->petugas->nama ?? '-' }}</strong></td>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td><span class="badge bg-secondary">{{ $item->shift }}</span></td>
                    <td>{{ $item->jam_masuk ?? '-' }}</td>
                    <td>{{ $item->jam_keluar ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ $item->status_badge }}">
                            {{ $item->status_hadir_label }}
                        </span>
                    </td>
                    <td>{{ $item->keterangan ?? '-' }}</td>
                    @if(in_array(auth()->user()->role, ['pengawas', 'it']))
                    <td>
                        <a href="{{ route('presensi.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $item->id }}, '{{ $item->petugas->nama ?? '' }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Belum ada data presensi untuk tanggal ini.
                    </td>
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
                    <i class="bi bi-person-x-fill text-danger" style="font-size:2rem;"></i>
                </div>
                <h6 class="mb-1">Hapus presensi ini?</h6>
                <p class="text-muted mb-0">Data presensi <strong id="namaPetugas"></strong> akan dihapus permanen.</p>
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
    document.getElementById('namaPetugas').textContent = nama;
    document.getElementById('formHapus').action = '/presensi/' + id;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
@endpush