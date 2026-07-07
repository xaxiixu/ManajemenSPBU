@extends('layouts.app')
@section('title', 'Pengajuan Lembur')
@section('page-title', 'Pengajuan Lembur')

@php
$statusInfo = [
    'pending'  => ['label' => 'Pending',  'badge' => 'warning'],
    'approved' => ['label' => 'Disetujui', 'badge' => 'success'],
    'rejected' => ['label' => 'Ditolak',  'badge' => 'danger'],
];
@endphp

@section('content')
<div class="page-header">
    <h2><i class="bi bi-clock-history me-2 text-danger"></i>Pengajuan Lembur</h2>
    <p>Ajukan lembur harian Anda</p>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Form Pengajuan</div>
            <div class="card-body">
                <form method="POST" action="{{ route('lembur.store') }}" id="formLembur">
                @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="date" name="tanggal" id="tanggalInput" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                        @error('tanggal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Jam Mulai</label>
                            <input type="text" id="jamMulaiDisplay" class="form-control bg-light" value="-" readonly tabindex="-1">
                            <small class="text-muted">Otomatis = jam selesai shift Anda tanggal tsb.</small>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Durasi Lembur</label>
                            <select id="durasiSelect" class="form-select" required disabled>
                                <option value="">-- Pilih tanggal dulu --</option>
                                <option value="1">1 Jam</option>
                                <option value="2">2 Jam</option>
                                <option value="3">3 Jam</option>
                                <option value="4">4 Jam</option>
                            </select>
                            <input type="hidden" name="jam_selesai" id="jamSelesaiValue">
                            @error('jam_selesai')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <small id="shiftHelp" class="text-muted d-block mb-3"></small>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan</label>
                        <textarea name="alasan" class="form-control" rows="3" required>{{ old('alasan') }}</textarea>
                        @error('alasan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" id="submitLembur" class="btn btn-danger"><i class="bi bi-send me-1"></i>Ajukan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Riwayat Lembur Saya</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Durasi</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr>
                            <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $item->jam_mulai }} - {{ $item->jam_selesai }}</td>
                            <td>{{ $item->durasi_label }}</td>
                            <td>{{ $item->alasan }}</td>
                            <td>
                                <span class="badge bg-{{ $statusInfo[$item->status]['badge'] }}">
                                    {{ $statusInfo[$item->status]['label'] }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $item->catatan_approval ?? '-' }}</td>
                            <td>
                                @if($item->status === 'pending')
                                <a href="{{ route('lembur.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="confirmBatal('{{ route('lembur.destroy', $item) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @else
                                <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada pengajuan lembur.</td></tr>
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
                <h6 class="mb-1">Batalkan pengajuan lembur ini?</h6>
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

const tanggalInput    = document.getElementById('tanggalInput');
const jamMulaiDisplay = document.getElementById('jamMulaiDisplay');
const shiftHelp       = document.getElementById('shiftHelp');
const submitLembur    = document.getElementById('submitLembur');
const durasiSelect    = document.getElementById('durasiSelect');
const jamSelesaiValue = document.getElementById('jamSelesaiValue');

// Hitung jam selesai = jamMulai (HH:MM) + tambahJam, lintas tengah malam aware.
function hitungJamSelesai(jamMulai, tambahJam) {
    const [h, m] = jamMulai.split(':').map(Number);
    let totalMenit = h * 60 + m + tambahJam * 60;
    const besok = totalMenit >= 24 * 60;
    totalMenit = totalMenit % (24 * 60);
    const jam = String(Math.floor(totalMenit / 60)).padStart(2, '0');
    const menit = String(totalMenit % 60).padStart(2, '0');
    return { jam: `${jam}:${menit}`, besok };
}

function perbaruiOpsiDurasi() {
    const jamMulai = jamMulaiDisplay.value;
    if (!jamMulai || jamMulai === '-' || jamMulai === 'Memuat...') return;

    [1, 2, 3, 4].forEach(jam => {
        const opt = durasiSelect.querySelector(`option[value="${jam}"]`);
        const hasil = hitungJamSelesai(jamMulai, jam);
        opt.textContent = `${jam} Jam (selesai ${hasil.jam}${hasil.besok ? ' besok' : ''})`;
    });

    updateJamSelesaiValue();
}

function updateJamSelesaiValue() {
    const jamMulai = jamMulaiDisplay.value;
    if (!jamMulai || jamMulai === '-' || jamMulai === 'Memuat...' || !durasiSelect.value) {
        jamSelesaiValue.value = '';
        return;
    }

    jamSelesaiValue.value = hitungJamSelesai(jamMulai, parseInt(durasiSelect.value, 10)).jam;
}

durasiSelect.addEventListener('change', updateJamSelesaiValue);

async function muatJamMulai() {
    const tanggal = tanggalInput.value;

    if (!tanggal) {
        jamMulaiDisplay.value = '-';
        shiftHelp.textContent = '';
        return;
    }

    jamMulaiDisplay.value = 'Memuat...';
    submitLembur.disabled = true;
    durasiSelect.disabled = true;
    durasiSelect.value = '';

    try {
        const url = '{{ route('lembur.jam-mulai-tersedia') }}?tanggal=' + encodeURIComponent(tanggal);
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();

        if (!data.ditemukan) {
            jamMulaiDisplay.value = '-';
            shiftHelp.textContent = 'Anda belum tercatat hadir pada tanggal ini, tidak bisa mengajukan lembur.';
            shiftHelp.classList.add('text-danger');
            submitLembur.disabled = true;
            return;
        }

        jamMulaiDisplay.value = data.jam_mulai;
        shiftHelp.textContent = `Shift ${data.shift} - jam mulai lembur otomatis mengikuti jam selesai shift ini.`;
        shiftHelp.classList.remove('text-danger');
        durasiSelect.disabled = false;
        perbaruiOpsiDurasi();
        submitLembur.disabled = false;
    } catch (e) {
        jamMulaiDisplay.value = '-';
        shiftHelp.textContent = 'Gagal memuat data shift.';
        shiftHelp.classList.add('text-danger');
        submitLembur.disabled = true;
    }
}

tanggalInput.addEventListener('change', muatJamMulai);
if (tanggalInput.value) {
    muatJamMulai();
}
</script>
@endpush
