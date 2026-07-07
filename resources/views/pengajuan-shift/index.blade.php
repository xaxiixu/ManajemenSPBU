@extends('layouts.app')
@section('title', 'Pengajuan Shift')
@section('page-title', 'Pengajuan Shift')

@php
$statusInfo = [
    'pending'  => ['label' => 'Pending',  'badge' => 'warning'],
    'approved' => ['label' => 'Disetujui', 'badge' => 'success'],
    'rejected' => ['label' => 'Ditolak',  'badge' => 'danger'],
];
@endphp

@section('content')
<div class="page-header">
    <h2><i class="bi bi-arrow-repeat me-2 text-danger"></i>Pengajuan Shift</h2>
    <p>Ajukan perubahan shift default Anda</p>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Form Pengajuan</div>
            <div class="card-body">
                <form method="POST" action="{{ route('pengajuan-shift.store') }}">
                @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Shift Diminta</label>
                        <select name="shift_diminta" class="form-select" required>
                            <option value="">-- Pilih Shift --</option>
                            <option value="Pagi"  {{ old('shift_diminta') == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                            <option value="Siang" {{ old('shift_diminta') == 'Siang' ? 'selected' : '' }}>Siang</option>
                            <option value="Malam" {{ old('shift_diminta') == 'Malam' ? 'selected' : '' }}>Malam</option>
                        </select>
                        @error('shift_diminta')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Berlaku Mulai Tanggal</label>
                        <input type="date" name="tanggal_berlaku" class="form-control" value="{{ old('tanggal_berlaku') }}" required>
                        @error('tanggal_berlaku')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan</label>
                        <textarea name="alasan" class="form-control" rows="3" required>{{ old('alasan') }}</textarea>
                        @error('alasan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-send me-1"></i>Ajukan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Riwayat Pengajuan Saya</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Shift Diminta</th>
                            <th>Berlaku</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $item->shift_diminta }}</span></td>
                            <td>{{ $item->tanggal_berlaku->format('d/m/Y') }}</td>
                            <td>{{ $item->alasan }}</td>
                            <td>
                                <span class="badge bg-{{ $statusInfo[$item->status]['badge'] }}">
                                    {{ $statusInfo[$item->status]['label'] }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $item->catatan_approval ?? '-' }}</td>
                            <td>
                                @if($item->status === 'pending')
                                <a href="{{ route('pengajuan-shift.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="confirmBatal('{{ route('pengajuan-shift.destroy', $item) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @else
                                <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada pengajuan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal konfirmasi batalkan pengajuan --}}
<div class="modal fade" id="modalBatal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Batalkan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div style="width:70px;height:70px;background:#fdf0ef;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <i class="bi bi-trash-fill text-danger" style="font-size:2rem;"></i>
                </div>
                <h6 class="mb-1">Batalkan pengajuan ini?</h6>
                <p class="text-muted mb-0">Pengajuan yang dibatalkan tidak bisa dikembalikan.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                <form id="formBatal" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-trash me-1"></i>Ya, Batalkan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmBatal(url) {
    document.getElementById('formBatal').action = url;
    new bootstrap.Modal(document.getElementById('modalBatal')).show();
}
</script>
@endpush
