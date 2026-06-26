@extends('layouts.app')
@section('title', 'Tambah Akun COA')
@section('page-title', 'Tambah Akun COA')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-plus-circle me-2 text-danger"></i>Tambah Akun COA</h2>
    <p>Tambah akun keuangan baru</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('coa.store') }}" method="POST">
        @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Kode Akun</label>
                <input type="text" name="kode_akun" class="form-control"
                    placeholder="cth: 4-1400" value="{{ old('kode_akun') }}" required>
                @error('kode_akun')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Akun</label>
                <input type="text" name="nama_akun" class="form-control"
                    placeholder="cth: Pendapatan Penjualan Dexlite"
                    value="{{ old('nama_akun') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Kategori</label>
                <select name="kategori" class="form-select" required id="kategori">
                    <option value="">-- Pilih Kategori --</option>
                    <option value="aset"       {{ old('kategori') == 'aset'       ? 'selected' : '' }}>Aset</option>
                    <option value="kewajiban"  {{ old('kategori') == 'kewajiban'  ? 'selected' : '' }}>Kewajiban</option>
                    <option value="modal"      {{ old('kategori') == 'modal'      ? 'selected' : '' }}>Modal</option>
                    <option value="pendapatan" {{ old('kategori') == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                    <option value="beban"      {{ old('kategori') == 'beban'      ? 'selected' : '' }}>Beban</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Posisi Normal</label>
                <select name="posisi_normal" class="form-select" required id="posisi">
                    <option value="">-- Pilih Posisi --</option>
                    <option value="debit"  {{ old('posisi_normal') == 'debit'  ? 'selected' : '' }}>Debit</option>
                    <option value="kredit" {{ old('posisi_normal') == 'kredit' ? 'selected' : '' }}>Kredit</option>
                </select>
                <small class="text-muted">Aset & Beban = Debit | Kewajiban, Modal & Pendapatan = Kredit</small>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="deskripsi" class="form-control" value="{{ old('deskripsi') }}">
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('coa.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto set posisi normal berdasarkan kategori
document.getElementById('kategori').addEventListener('change', function() {
    const posisi = document.getElementById('posisi');
    const map = {
        'aset': 'debit', 'beban': 'debit',
        'kewajiban': 'kredit', 'modal': 'kredit', 'pendapatan': 'kredit'
    };
    if (map[this.value]) posisi.value = map[this.value];
});
</script>
@endpush