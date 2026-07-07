@extends('layouts.app')
@section('title', 'Data Petugas')
@section('page-title', 'Data Petugas')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-person-badge-fill me-2 text-danger"></i>Data Petugas</h2>
        <p>Daftar petugas dan kehadiran per shift hari ini</p>
    </div>
    @if(auth()->user()->role === 'it')
    <a href="{{ route('users.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah Akun Petugas
    </a>
    @endif
</div>

@if ($tanggalOperasional['lintas_tengah_malam'])
<div class="alert alert-warning">
    <i class="bi bi-moon-stars-fill me-1"></i>
    Masih dalam Shift {{ $tanggalOperasional['shift_aktif'] }} yang dimulai kemarin — data "hari ini" di bawah
    ini merujuk ke tanggal <strong>{{ $tanggalOperasional['tanggal']->format('d/m/Y') }}</strong>, bukan tanggal kalender sekarang.
</div>
@endif

{{-- Hadir per shift hari ini --}}
<div class="row g-3 mb-3">
    @foreach(['Pagi', 'Siang', 'Malam'] as $shift)
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                Shift {{ $shift }}
                <span class="badge bg-success">{{ ($hadirHariIni[$shift] ?? collect())->count() }} hadir</span>
            </div>
            <div class="card-body">
                @forelse(($hadirHariIni[$shift] ?? collect()) as $absen)
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span>{{ $absen->user->name ?? 'Petugas dihapus' }}</span>
                    <small class="text-muted">{{ $absen->jam_masuk }}</small>
                </div>
                @empty
                <p class="text-muted small mb-0">Belum ada yang hadir.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">Daftar Petugas</div>
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
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($petugas as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->nik ?? '-' }}</td>
                    <td><span class="badge bg-secondary text-capitalize">{{ $item->jabatan ?? '-' }}</span></td>
                    <td>{{ $item->shift_default ?? '-' }}</td>
                    <td>{{ $item->no_hp ?? '-' }}</td>
                    <td>
                        @if($item->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('petugas.show', $item->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-warning"
                            onclick="bukaModalAbsensi({{ $item->id }}, '{{ $item->name }}')">
                            <i class="bi bi-calendar-x"></i> Tandai Absen
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data petugas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal tandai sakit/izin/tidak hadir --}}
<div class="modal fade" id="modalAbsensi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formAbsensi" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Tandai Presensi — <span id="namaPetugasModal"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status_hadir" class="form-select" required>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                            <option value="tidak_hadir">Tidak Hadir</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bukaModalAbsensi(id, nama) {
    document.getElementById('namaPetugasModal').textContent = nama;
    document.getElementById('formAbsensi').action = '/petugas/' + id + '/mark-absensi';
    new bootstrap.Modal(document.getElementById('modalAbsensi')).show();
}
</script>
@endpush
