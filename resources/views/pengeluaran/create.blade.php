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
                    @foreach($coa as $c)
                    <option value="{{ $c->id }}" {{ old('coa_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->kode_akun }} — {{ $c->nama_akun }}
                    </option>
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
                <input type="number" name="jumlah" class="form-control"
                    placeholder="cth: 500000"
                    value="{{ old('jumlah') }}" required>
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
@endsection