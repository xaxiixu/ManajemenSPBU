@extends('layouts.app')
@section('title', 'Edit Jenis BBM')
@section('page-title', 'Edit Jenis BBM')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit Jenis BBM</h2>
    <p>Update data {{ $masterBbm->jenis_bbm }}</p>
</div>

<div class="row g-3">
    <div class="col-md-7" style="max-width:600px;">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('master-bbm.update', $masterBbm) }}" method="POST">
                @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis BBM</label>
                        <input type="text" name="jenis_bbm" class="form-control"
                            value="{{ old('jenis_bbm', $masterBbm->jenis_bbm) }}" required>
                        @error('jenis_bbm')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">RON <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="ron" class="form-control"
                            value="{{ old('ron', $masterBbm->ron) }}">
                        @error('ron')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Harga per Liter (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="hargaDisplay" class="form-control"
                                value="{{ number_format(old('harga_per_liter', $masterBbm->harga_per_liter), 0, ',', '.') }}"
                                autocomplete="off" required>
                            <input type="hidden" name="harga_per_liter" id="hargaValue"
                                value="{{ old('harga_per_liter', $masterBbm->harga_per_liter) }}">
                        </div>
                        <small class="text-muted">Perubahan harga hanya berlaku untuk transaksi baru; transaksi lama tetap memakai harga saat itu (snapshot).</small>
                        @error('harga_per_liter')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Akun Pendapatan (COA)</label>
                        <select name="coa_pendapatan_id" class="form-select" required>
                            <option value="">-- Pilih Akun Pendapatan --</option>
                            @foreach($coaPendapatan as $item)
                                @if($item->children->count() > 0)
                                <optgroup label="{{ $item->kode_akun }} — {{ $item->nama_akun }}">
                                    @foreach($item->children as $child)
                                    <option value="{{ $child->id }}" {{ old('coa_pendapatan_id', $masterBbm->coa_pendapatan_id) == $child->id ? 'selected' : '' }}>
                                        {{ $child->kode_akun }} — {{ $child->nama_akun }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @else
                                <option value="{{ $item->id }}" {{ old('coa_pendapatan_id', $masterBbm->coa_pendapatan_id) == $item->id ? 'selected' : '' }}>
                                    {{ $item->kode_akun }} — {{ $item->nama_akun }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                        @error('coa_pendapatan_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="is_aktif" class="form-select" required>
                            <option value="1" {{ old('is_aktif', $masterBbm->is_aktif) ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ !old('is_aktif', $masterBbm->is_aktif) ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                        <small class="text-muted">Nonaktif = tidak muncul di dropdown jenis BBM saat input penjualan baru.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('master-bbm.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Update</button>
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
