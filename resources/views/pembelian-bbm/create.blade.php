@extends('layouts.app')
@section('title', 'Tambah Pembelian BBM')
@section('page-title', 'Tambah Pembelian BBM')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-truck me-2 text-info"></i>Tambah Pembelian BBM</h2>
    <p>Input data pembelian BBM dari supplier</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('pembelian-bbm.store') }}" method="POST">
        @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                @error('tanggal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Supplier</label>
                <input type="text" name="nama_supplier" class="form-control"
                    placeholder="cth: PT Pertamina Patra Niaga"
                    value="{{ old('nama_supplier') }}" required>
                @error('nama_supplier')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Tangki Tujuan</label>
                <select name="tangki_id" class="form-select" required>
                    <option value="">-- Pilih Tangki --</option>
                    @foreach($tangkis as $t)
                    <option value="{{ $t->id }}" {{ old('tangki_id') == $t->id ? 'selected' : '' }}>
                        {{ $t->nama_tangki }} ({{ $t->masterBbm->jenis_bbm ?? '-' }})
                        — stok saat ini {{ number_format($t->stok_liter) }} L, HPP rata² Rp {{ number_format($t->harga_pokok_rata2) }}/L
                    </option>
                    @endforeach
                </select>
                @error('tangki_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Volume (Liter)</label>
                <input type="number" name="volume_liter" class="form-control" min="1"
                    value="{{ old('volume_liter') }}" required>
                @error('volume_liter')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Harga Beli per Liter (Rp)</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="text" id="hargaDisplay" class="form-control"
                        placeholder="cth: 9.500"
                        value="{{ old('harga_per_liter') ? number_format(old('harga_per_liter'), 0, ',', '.') : '' }}"
                        autocomplete="off" required>
                    <input type="hidden" name="harga_per_liter" id="hargaValue" value="{{ old('harga_per_liter') }}">
                </div>
                @error('harga_per_liter')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Status Pembayaran</label>
                <select name="status_bayar" class="form-select" required>
                    <option value="tunai" {{ old('status_bayar') == 'tunai' ? 'selected' : '' }}>Tunai (bayar langsung, Kas berkurang)</option>
                    <option value="kredit" {{ old('status_bayar') == 'kredit' ? 'selected' : '' }}>Kredit (belum dibayar, jadi Utang Usaha)</option>
                </select>
                @error('status_bayar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pembelian-bbm.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-info text-white"><i class="bi bi-save me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
const display = document.getElementById('hargaDisplay');
const hidden  = document.getElementById('hargaValue');

display.addEventListener('input', function () {
    let raw = this.value.replace(/\D/g, '');
    this.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    hidden.value = raw;
});

display.closest('form').addEventListener('submit', function () {
    hidden.value = display.value.replace(/\D/g, '');
});
</script>
@endpush
@endsection
