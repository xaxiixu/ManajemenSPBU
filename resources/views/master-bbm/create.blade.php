@extends('layouts.app')
@section('title', 'Tambah Jenis BBM')
@section('page-title', 'Tambah Jenis BBM')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-plus-circle me-2 text-danger"></i>Tambah Jenis BBM</h2>
    <p>Tambah jenis BBM, RON, dan harga per liter baru</p>
</div>

<div class="row g-3">
    <div class="col-md-7" style="max-width:600px;">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('master-bbm.store') }}" method="POST">
                @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis BBM</label>
                        <input type="text" name="jenis_bbm" class="form-control"
                            placeholder="cth: Pertalite" value="{{ old('jenis_bbm') }}" required>
                        @error('jenis_bbm')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">RON <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="ron" class="form-control"
                            placeholder="cth: 90 (kosongkan untuk Solar)" value="{{ old('ron') }}">
                        @error('ron')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga per Liter (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="hargaDisplay" class="form-control"
                                placeholder="cth: 10.000"
                                value="{{ old('harga_per_liter') ? number_format(old('harga_per_liter'), 0, ',', '.') : '' }}"
                                autocomplete="off" required>
                            <input type="hidden" name="harga_per_liter" id="hargaValue" value="{{ old('harga_per_liter') }}">
                        </div>
                        @error('harga_per_liter')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Akun Pendapatan (COA)</label>
                        <select name="coa_pendapatan_id" class="form-select" required>
                            <option value="">-- Pilih Akun Pendapatan --</option>
                            @foreach($coaPendapatan as $c)
                            <option value="{{ $c->id }}" {{ old('coa_pendapatan_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->kode_akun }} — {{ $c->nama_akun }}
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Akun ini dipakai jurnal otomatis saat ada penjualan jenis BBM ini.</small>
                        @error('coa_pendapatan_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('master-bbm.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const hargaDisplay = document.getElementById('hargaDisplay');
const hargaValue   = document.getElementById('hargaValue');

hargaDisplay.addEventListener('input', function () {
    let raw = this.value.replace(/\D/g, '');
    this.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    hargaValue.value = raw;
});

hargaDisplay.closest('form').addEventListener('submit', function () {
    hargaValue.value = hargaDisplay.value.replace(/\D/g, '');
});
</script>
@endpush
