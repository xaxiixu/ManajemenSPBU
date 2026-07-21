@extends('layouts.app')
@section('title', 'Pengaturan Gaji')
@section('page-title', 'Pengaturan Gaji')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-sliders me-2 text-danger"></i>Pengaturan Penggajian</h2>
    <p>Parameter global perhitungan gaji petugas. Berlaku untuk periode payroll yang di-generate setelah perubahan ini disimpan.</p>
</div>

<div class="card" style="max-width:640px;">
    <div class="card-body">
        <form action="{{ route('pengaturan-gaji.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Tanggal Gajian</label>
                <div class="input-group">
                    <span class="input-group-text">Tiap tanggal</span>
                    <input type="number" name="tanggal_gajian" class="form-control" min="1" max="31"
                        value="{{ old('tanggal_gajian', $setting->tanggal_gajian) }}" required>
                </div>
                <div class="form-text">
                    Periode kerja = (tanggal gajian + 1) bulan sebelumnya s/d tanggal gajian bulan ini.
                    Contoh: tanggal 25 → periode 26 (bulan lalu) s/d 25 (bulan ini). Kalau melebihi jumlah
                    hari bulan tertentu (mis. 31 di Februari), otomatis dipakai hari terakhir bulan itu.
                </div>
                @error('tanggal_gajian')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Rate Lembur per Jam</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="rate_lembur_per_jam" class="form-control" min="0"
                        value="{{ old('rate_lembur_per_jam', $setting->rate_lembur_per_jam) }}" required>
                    <span class="input-group-text">/ jam</span>
                </div>
                <div class="form-text">Berlaku sama untuk semua petugas, dikalikan jumlah jam lembur yang di-approve.</div>
                @error('rate_lembur_per_jam')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Rate Potongan Telat per Menit</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="rate_potongan_telat_per_menit" class="form-control" min="0"
                        value="{{ old('rate_potongan_telat_per_menit', $setting->rate_potongan_telat_per_menit) }}" required>
                    <span class="input-group-text">/ menit</span>
                </div>
                <div class="form-text">Hanya kelebihan menit di atas toleransi yang dipotong.</div>
                @error('rate_potongan_telat_per_menit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Toleransi Telat</label>
                <div class="input-group">
                    <input type="number" name="toleransi_telat_menit" class="form-control" min="0"
                        value="{{ old('toleransi_telat_menit', $setting->toleransi_telat_menit) }}" required>
                    <span class="input-group-text">menit</span>
                </div>
                <div class="form-text">Telat ≤ toleransi tidak dipotong. Di atasnya, hanya kelebihannya yang dipotong.</div>
                @error('toleransi_telat_menit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Kuota Izin / Sakit per Bulan</label>
                <div class="input-group">
                    <input type="number" name="kuota_izin_sakit_per_bulan" class="form-control" min="0"
                        value="{{ old('kuota_izin_sakit_per_bulan', $setting->kuota_izin_sakit_per_bulan) }}" required>
                    <span class="input-group-text">hari</span>
                </div>
                <div class="form-text">
                    Total izin + sakit dalam kuota tidak dipotong. Yang melebihi kuota dipotong setengah hari
                    kerja per kejadian. Alpha (tidak hadir) selalu dipotong penuh 1 hari, di luar kuota ini.
                </div>
                @error('kuota_izin_sakit_per_bulan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-save me-1"></i>Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
