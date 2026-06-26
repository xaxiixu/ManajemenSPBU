@extends('layouts.app')
@section('title', 'Tambah Penjualan BBM')
@section('page-title', 'Tambah Penjualan BBM')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-plus-circle me-2 text-danger"></i>Tambah Penjualan BBM</h2>
    <p>Input data penjualan satu nozzle</p>
</div>

<form action="{{ route('penjualan-bbm.store') }}" method="POST" enctype="multipart/form-data">
@csrf
<div class="row g-3">

    {{-- Kolom Kiri --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Informasi Shift</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Shift</label>
                    <select name="shift" class="form-select" required>
                        <option value="">-- Pilih Shift --</option>
                        <option value="Pagi">Pagi</option>
                        <option value="Siang">Siang</option>
                        <option value="Malam">Malam</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Operator Bertugas</label>
                    <select name="absensis_id" class="form-select">
                        <option value="">-- Pilih Operator (opsional) --</option>
                        @foreach($absensis as $a)
                        <option value="{{ $a->id }}">{{ $a->petugas->nama }} ({{ $a->shift }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Menampilkan petugas hadir hari ini</small>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold">Pulau</label>
                        <input type="text" name="pulau" class="form-control" placeholder="cth: 1" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold">Nozzle</label>
                        <input type="text" name="nozzle" class="form-control" placeholder="cth: 1A" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenis BBM</label>
                    <select name="jenis_bbm" class="form-select" required>
                        <option value="">-- Pilih Jenis BBM --</option>
                        <option value="Pertalite">Pertalite</option>
                        <option value="Pertamax">Pertamax</option>
                        <option value="Solar">Solar</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Harga per Liter (Rp)</label>
                    <input type="number" name="harga_per_liter" class="form-control" placeholder="cth: 10000" required>
                </div>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Data Meter & Foto</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Meter Awal</label>
                    <input type="number" name="meter_awal" class="form-control" id="meterAwal" placeholder="Angka meter awal" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Foto Meter Awal</label>
                    <input type="file" name="foto_meter_awal" class="form-control" accept="image/*" required>
                    <small class="text-muted">Format JPG/PNG, maks 2MB</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Meter Akhir</label>
                    <input type="number" name="meter_akhir" class="form-control" id="meterAkhir" placeholder="Angka meter akhir" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Foto Meter Akhir</label>
                    <input type="file" name="foto_meter_akhir" class="form-control" accept="image/*" required>
                    <small class="text-muted">Format JPG/PNG, maks 2MB</small>
                </div>

                {{-- Preview kalkulasi --}}
                <div class="p-3 rounded" style="background:#f8f9fa; border: 1px dashed #dee2e6;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Liter Terjual:</span>
                        <strong id="previewLiter">— L</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Total Penjualan:</span>
                        <strong id="previewTotal" class="text-danger">— </strong>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-semibold">Catatan (opsional)</label>
                    <textarea name="catatan" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="{{ route('penjualan-bbm.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan</button>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
    function updatePreview() {
        const awal   = parseFloat(document.getElementById('meterAwal').value) || 0;
        const akhir  = parseFloat(document.getElementById('meterAkhir').value) || 0;
        const harga  = parseFloat(document.querySelector('[name=harga_per_liter]').value) || 0;
        const liter  = akhir - awal;
        const total  = liter * harga;

        document.getElementById('previewLiter').textContent =
            liter > 0 ? liter.toLocaleString('id-ID') + ' L' : '— L';
        document.getElementById('previewTotal').textContent =
            total > 0 ? 'Rp ' + total.toLocaleString('id-ID') : '—';
    }

    document.getElementById('meterAwal').addEventListener('input', updatePreview);
    document.getElementById('meterAkhir').addEventListener('input', updatePreview);
    document.querySelector('[name=harga_per_liter]').addEventListener('input', updatePreview);
</script>
@endpush