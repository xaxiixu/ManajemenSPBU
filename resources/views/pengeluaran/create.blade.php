@extends('layouts.app')
@section('title', 'Tambah Pengeluaran')
@section('page-title', 'Tambah Pengeluaran')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-plus-circle me-2 text-danger"></i>Tambah Pengeluaran</h2>
    <p>Input data pengeluaran operasional</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('pengeluaran.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Kategori Pengeluaran</label>
                <select name="coa_id" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($coa as $item)
                        @if($item->children->count() > 0)
                        <optgroup label="{{ $item->kode_akun }} — {{ $item->nama_akun }}">
                            @foreach($item->children as $child)
                            <option value="{{ $child->id }}" {{ old('coa_id') == $child->id ? 'selected' : '' }}>
                                {{ $child->kode_akun }} — {{ $child->nama_akun }}
                            </option>
                            @endforeach
                        </optgroup>
                        @else
                        <option value="{{ $item->id }}" {{ old('coa_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->kode_akun }} — {{ $item->nama_akun }}
                        </option>
                        @endif
                    @endforeach
                </select>
                @error('coa_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Keterangan</label>
                <input type="text" name="keterangan" class="form-control"
                    placeholder="cth: Bayar tagihan listrik bulan Mei"
                    value="{{ old('keterangan') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Jumlah (Rp)</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="text" id="jumlahDisplay" class="form-control"
                        placeholder="cth: 500.000"
                        value="{{ old('jumlah') ? number_format(old('jumlah'), 0, ',', '.') : '' }}"
                        autocomplete="off" required>
                    <input type="hidden" name="jumlah" id="jumlahValue" value="{{ old('jumlah') }}">
                </div>
                <small class="text-muted">Gunakan titik sebagai pemisah ribuan. Contoh: 500.000 atau 1.500.000</small>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Bukti Pembayaran <span class="text-muted fw-normal">(opsional)</span>
                </label>
                <input type="file" name="bukti_pembayaran" class="form-control" accept="image/*">
                <small class="text-muted">Foto struk/kwitansi, maks 2MB</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pengeluaran.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
const display = document.getElementById('jumlahDisplay');
const hidden  = document.getElementById('jumlahValue');

display.addEventListener('input', function () {
    // Hapus semua karakter selain angka
    let raw = this.value.replace(/\D/g, '');
    // Format dengan titik
    this.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    // Simpan nilai asli ke hidden field
    hidden.value = raw;
});

// Pastikan saat submit, nilai hidden terisi
display.closest('form').addEventListener('submit', function () {
    hidden.value = display.value.replace(/\D/g, '');
});
</script>
@endpush
@endsection