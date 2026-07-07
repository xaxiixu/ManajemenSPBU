@extends('layouts.app')
@section('title', 'Edit Pengajuan Shift')
@section('page-title', 'Edit Pengajuan Shift')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit Pengajuan Shift</h2>
    <p>Ubah pengajuan yang masih menunggu approval</p>
</div>

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <form method="POST" action="{{ route('pengajuan-shift.update', $pengajuanShift) }}">
        @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Shift Diminta</label>
                <select name="shift_diminta" class="form-select" required>
                    <option value="">-- Pilih Shift --</option>
                    <option value="Pagi"  {{ old('shift_diminta', $pengajuanShift->shift_diminta) == 'Pagi'  ? 'selected' : '' }}>Pagi</option>
                    <option value="Siang" {{ old('shift_diminta', $pengajuanShift->shift_diminta) == 'Siang' ? 'selected' : '' }}>Siang</option>
                    <option value="Malam" {{ old('shift_diminta', $pengajuanShift->shift_diminta) == 'Malam' ? 'selected' : '' }}>Malam</option>
                </select>
                @error('shift_diminta')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Berlaku Mulai Tanggal</label>
                <input type="date" name="tanggal_berlaku" class="form-control"
                    value="{{ old('tanggal_berlaku', $pengajuanShift->tanggal_berlaku->format('Y-m-d')) }}" required>
                @error('tanggal_berlaku')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Alasan</label>
                <textarea name="alasan" class="form-control" rows="3" required>{{ old('alasan', $pengajuanShift->alasan) }}</textarea>
                @error('alasan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pengajuan-shift.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
