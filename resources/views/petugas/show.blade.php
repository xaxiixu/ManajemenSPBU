@extends('layouts.app')
@section('title', 'Detail Petugas')
@section('page-title', 'Detail Petugas')

@php
$statusInfo = [
    'hadir'       => ['label' => 'Hadir',       'badge' => 'success'],
    'sakit'       => ['label' => 'Sakit',       'badge' => 'warning'],
    'izin'        => ['label' => 'Izin',        'badge' => 'info'],
    'tidak_hadir' => ['label' => 'Tidak Hadir', 'badge' => 'danger'],
];
@endphp

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-person-badge-fill me-2 text-danger"></i>{{ $petugas->name }}</h2>
        <p>{{ ucfirst($petugas->jabatan ?? '-') }} — Shift default {{ $petugas->shift_default ?? '-' }}</p>
    </div>
    <a href="{{ route('petugas.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Informasi Petugas</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" width="40%">NIK</td><td>{{ $petugas->nik ?? '-' }}</td></tr>
                    <tr><td class="text-muted">No. HP</td><td>{{ $petugas->no_hp ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Email</td><td>{{ $petugas->email }}</td></tr>
                    <tr>
                        <td class="text-muted">Status Akun</td>
                        <td>
                            @if($petugas->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Riwayat Presensi (30 terakhir)</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Shift</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status</th>
                    <th>Menit Telat</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td><span class="badge bg-secondary">{{ $item->shift }}</span></td>
                    <td>
                        {{ $item->jam_masuk ?? '-' }}
                        @if($item->status_hadir === 'hadir' && !$item->jam_keluar)
                            <span class="badge bg-warning text-dark ms-1">Sesi terbuka</span>
                        @endif
                    </td>
                    <td>{{ $item->jam_keluar ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ $statusInfo[$item->status_hadir]['badge'] ?? 'secondary' }}">
                            {{ $statusInfo[$item->status_hadir]['label'] ?? $item->status_hadir }}
                        </span>
                    </td>
                    <td>{{ $item->menit_telat > 0 ? $item->menit_telat . ' menit' : '-' }}</td>
                    <td>{{ $item->keterangan ?? '-' }}</td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="bukaModalEditAbsensi({{ $item->id }}, '{{ $item->status_hadir }}', '{{ substr($item->jam_masuk ?? '', 0, 5) }}', '{{ substr($item->jam_keluar ?? '', 0, 5) }}', '{{ addslashes($item->keterangan ?? '') }}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="bukaModalHapusAbsensi({{ $item->id }}, '{{ $item->tanggal->format('d/m/Y') }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat presensi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal edit record absensi --}}
<div class="modal fade" id="modalEditAbsensi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditAbsensi" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Koreksi Presensi — {{ $petugas->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status_hadir" id="editStatusHadir" class="form-select" required>
                            <option value="hadir">Hadir</option>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                            <option value="tidak_hadir">Tidak Hadir</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Jam Masuk</label>
                            <input type="time" name="jam_masuk" id="editJamMasuk" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Jam Keluar</label>
                            <input type="time" name="jam_keluar" id="editJamKeluar" class="form-control">
                        </div>
                    </div>
                    <p class="text-muted small">Menit telat dihitung ulang otomatis dari jam masuk & shift.</p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" id="editKeterangan" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal hapus record absensi --}}
<div class="modal fade" id="modalHapusAbsensi" tabindex="-1">
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
                    <i class="bi bi-calendar-x-fill text-danger" style="font-size:2rem;"></i>
                </div>
                <h6 class="mb-1">Hapus data presensi tanggal <span id="tanggalHapusAbsensi"></span>?</h6>
                <p class="text-muted mb-0">Data yang dihapus tidak bisa dikembalikan.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                <form id="formHapusAbsensi" method="POST">
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
function bukaModalEditAbsensi(id, statusHadir, jamMasuk, jamKeluar, keterangan) {
    document.getElementById('formEditAbsensi').action = '/absensi/' + id;
    document.getElementById('editStatusHadir').value = statusHadir;
    document.getElementById('editJamMasuk').value = jamMasuk;
    document.getElementById('editJamKeluar').value = jamKeluar;
    document.getElementById('editKeterangan').value = keterangan;
    new bootstrap.Modal(document.getElementById('modalEditAbsensi')).show();
}

function bukaModalHapusAbsensi(id, tanggal) {
    document.getElementById('tanggalHapusAbsensi').textContent = tanggal;
    document.getElementById('formHapusAbsensi').action = '/absensi/' + id;
    new bootstrap.Modal(document.getElementById('modalHapusAbsensi')).show();
}
</script>
@endpush
