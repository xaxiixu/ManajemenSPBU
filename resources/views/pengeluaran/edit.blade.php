@extends('layouts.app')
@section('title', 'Edit Pengeluaran')
@section('page-title', 'Edit Pengeluaran')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit Pengeluaran</h2>
    <p>Update data pengeluaran</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('pengeluaran.update', $pengeluaran) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Tanggal</label>
                <input type="date" name="tanggal" class="form-control"
                    value="{{ old('tanggal', $pengeluaran->tanggal->format('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Kategori Pengeluaran</label>
                <select name="coa_id" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($coa as $c)
                    <option value="{{ $c->id }}" {{ $pengeluaran->coa_id == $c->id ? 'selected' : '' }}>
                        {{ $c->kode_akun }} — {{ $c->nama_akun }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Keterangan</label>
                <input type="text" name="keterangan" class="form-control"
                    value="{{ old('keterangan', $pengeluaran->keterangan) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Jumlah (Rp)</label>
                <input type="number" name="jumlah" class="form-control"
                    value="{{ old('jumlah', $pengeluaran->jumlah) }}" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Bukti Pembayaran</label>
                @if($pengeluaran->bukti_pembayaran)
                <div class="mb-2">
                    <img src="{{ asset('storage/' . $pengeluaran->bukti_pembayaran) }}"
                        class="img-thumbnail" style="max-height:120px;">
                    <small class="d-block text-muted mt-1">Upload baru untuk mengganti</small>
                </div>
                @endif
                <input type="file" name="bukti_pembayaran" class="form-control" accept="image/*">
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pengeluaran.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Update</button>
            </div>
        </form>
    </div>
</div>
@endsection