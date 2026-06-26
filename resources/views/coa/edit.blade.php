@extends('layouts.app')
@section('title', 'Edit Akun COA')
@section('page-title', 'Edit Akun COA')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit Akun COA</h2>
    <p>Update akun {{ $coa->kode_akun }} — {{ $coa->nama_akun }}</p>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('coa.update', $coa) }}" method="POST">
        @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Kode Akun</label>
                <input type="text" name="kode_akun" class="form-control"
                    value="{{ old('kode_akun', $coa->kode_akun) }}" required>
                @error('kode_akun')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Akun</label>
                <input type="text" name="nama_akun" class="form-control"
                    value="{{ old('nama_akun', $coa->nama_akun) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Kategori</label>
                <select name="kategori" class="form-select" required>
                    <option value="aset"       {{ $coa->kategori == 'aset'       ? 'selected' : '' }}>Aset</option>
                    <option value="kewajiban"  {{ $coa->kategori == 'kewajiban'  ? 'selected' : '' }}>Kewajiban</option>
                    <option value="modal"      {{ $coa->kategori == 'modal'      ? 'selected' : '' }}>Modal</option>
                    <option value="pendapatan" {{ $coa->kategori == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                    <option value="beban"      {{ $coa->kategori == 'beban'      ? 'selected' : '' }}>Beban</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Posisi Normal</label>
                <select name="posisi_normal" class="form-select" required>
                    <option value="debit"  {{ $coa->posisi_normal == 'debit'  ? 'selected' : '' }}>Debit</option>
                    <option value="kredit" {{ $coa->posisi_normal == 'kredit' ? 'selected' : '' }}>Kredit</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Deskripsi</label>
                <input type="text" name="deskripsi" class="form-control"
                    value="{{ old('deskripsi', $coa->deskripsi) }}">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select name="is_aktif" class="form-select" required>
                    <option value="1" {{ $coa->is_aktif ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ !$coa->is_aktif ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('coa.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Update</button>
            </div>
        </form>
    </div>
</div>
@endsection