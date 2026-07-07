@extends('layouts.app')
@section('title', 'Approval')
@section('page-title', 'Approval')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-check2-square me-2 text-danger"></i>Approval</h2>
    <p>Pengajuan shift & lembur yang menunggu persetujuan</p>
</div>

<div class="card mb-3">
    <div class="card-header">Pengajuan Shift ({{ $pengajuanShift->count() }})</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Petugas</th>
                    <th>Shift Diminta</th>
                    <th>Berlaku</th>
                    <th>Alasan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengajuanShift as $item)
                <tr>
                    <td><strong>{{ $item->user->name ?? 'Petugas dihapus' }}</strong></td>
                    <td><span class="badge bg-secondary">{{ $item->shift_diminta }}</span></td>
                    <td>{{ $item->tanggal_berlaku->format('d/m/Y') }}</td>
                    <td>{{ $item->alasan }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-success"
                            onclick="bukaModalAksi('{{ route('pengajuan-shift.approve', $item) }}', 'approve', '{{ $item->user->name ?? 'Petugas dihapus' }}', 'Pengajuan Shift {{ $item->shift_diminta }}')">
                            <i class="bi bi-check-lg"></i> Setujui
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="bukaModalAksi('{{ route('pengajuan-shift.reject', $item) }}', 'reject', '{{ $item->user->name ?? 'Petugas dihapus' }}', 'Pengajuan Shift {{ $item->shift_diminta }}')">
                            <i class="bi bi-x-lg"></i> Tolak
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada pengajuan shift yang pending.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">Pengajuan Lembur ({{ $lembur->count() }})</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Petugas</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Durasi</th>
                    <th>Alasan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lembur as $item)
                <tr>
                    <td><strong>{{ $item->user->name ?? 'Petugas dihapus' }}</strong></td>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $item->jam_mulai }} - {{ $item->jam_selesai }}</td>
                    <td>{{ $item->durasi_label }}</td>
                    <td>{{ $item->alasan }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-success"
                            onclick="bukaModalAksi('{{ route('lembur.approve', $item) }}', 'approve', '{{ $item->user->name ?? 'Petugas dihapus' }}', 'Pengajuan Lembur {{ $item->tanggal->format('d/m/Y') }}')">
                            <i class="bi bi-check-lg"></i> Setujui
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="bukaModalAksi('{{ route('lembur.reject', $item) }}', 'reject', '{{ $item->user->name ?? 'Petugas dihapus' }}', 'Pengajuan Lembur {{ $item->tanggal->format('d/m/Y') }}')">
                            <i class="bi bi-x-lg"></i> Tolak
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada pengajuan lembur yang pending.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal aksi (Setujui/Tolak) - dipakai bareng utk Pengajuan Shift & Lembur --}}
<div class="modal fade" id="modalAksi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formAksi" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="modalAksiTitle">Setujui Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="modalAksiDesc"></p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                        <textarea name="catatan_approval" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="modalAksiSubmitBtn" class="btn px-4"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bukaModalAksi(url, aksi, namaPetugas, labelPengajuan) {
    const form      = document.getElementById('formAksi');
    const title     = document.getElementById('modalAksiTitle');
    const desc      = document.getElementById('modalAksiDesc');
    const submitBtn = document.getElementById('modalAksiSubmitBtn');

    form.action = url;

    if (aksi === 'approve') {
        title.textContent = 'Setujui Pengajuan';
        desc.textContent  = `Setujui ${labelPengajuan} milik ${namaPetugas}?`;
        submitBtn.className = 'btn btn-success px-4';
        submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Ya, Setujui';
    } else {
        title.textContent = 'Tolak Pengajuan';
        desc.textContent  = `Tolak ${labelPengajuan} milik ${namaPetugas}?`;
        submitBtn.className = 'btn btn-danger px-4';
        submitBtn.innerHTML = '<i class="bi bi-x-lg me-1"></i>Ya, Tolak';
    }

    new bootstrap.Modal(document.getElementById('modalAksi')).show();
}
</script>
@endpush
