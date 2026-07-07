@extends('layouts.app')
@section('title', 'Edit Pengajuan Lembur')
@section('page-title', 'Edit Pengajuan Lembur')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit Pengajuan Lembur</h2>
    <p>Ubah pengajuan yang masih menunggu approval</p>
</div>

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <form method="POST" action="{{ route('lembur.update', $lembur) }}" id="formLembur">
        @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" id="tanggalInput" class="form-control"
                    value="{{ old('tanggal', $lembur->tanggal->format('Y-m-d')) }}" required>
                @error('tanggal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label fw-semibold">Jam Mulai</label>
                    <input type="text" id="jamMulaiDisplay" class="form-control bg-light"
                        value="{{ substr($lembur->jam_mulai, 0, 5) }}" readonly tabindex="-1">
                    <small class="text-muted">Otomatis = jam selesai shift Anda tanggal tsb.</small>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label fw-semibold">Jam Selesai</label>
                    <input type="time" name="jam_selesai" class="form-control"
                        value="{{ old('jam_selesai', substr($lembur->jam_selesai, 0, 5)) }}" required>
                    @error('jam_selesai')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <small id="shiftHelp" class="text-muted d-block mb-3">Jam selesai boleh lebih kecil dari jam mulai kalau lembur lintas tengah malam. Durasi maksimal 4 jam.</small>
            <div class="mb-4">
                <label class="form-label fw-semibold">Alasan</label>
                <textarea name="alasan" class="form-control" rows="3" required>{{ old('alasan', $lembur->alasan) }}</textarea>
                @error('alasan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('lembur.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" id="submitLembur" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const tanggalInput    = document.getElementById('tanggalInput');
const jamMulaiDisplay = document.getElementById('jamMulaiDisplay');
const shiftHelp       = document.getElementById('shiftHelp');
const submitLembur    = document.getElementById('submitLembur');
const helpDefault     = shiftHelp.textContent;

async function muatJamMulai() {
    const tanggal = tanggalInput.value;

    if (!tanggal) {
        jamMulaiDisplay.value = '-';
        return;
    }

    jamMulaiDisplay.value = 'Memuat...';
    submitLembur.disabled = true;

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
        shiftHelp.textContent = `Shift ${data.shift} - ` + helpDefault;
        shiftHelp.classList.remove('text-danger');
        submitLembur.disabled = false;
    } catch (e) {
        jamMulaiDisplay.value = '-';
        shiftHelp.textContent = 'Gagal memuat data shift.';
        shiftHelp.classList.add('text-danger');
        submitLembur.disabled = true;
    }
}

tanggalInput.addEventListener('change', muatJamMulai);
</script>
@endpush
