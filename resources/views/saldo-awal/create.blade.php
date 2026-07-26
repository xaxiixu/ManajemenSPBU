@extends('layouts.app')
@section('title', 'Saldo Awal')
@section('page-title', 'Saldo Awal')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-piggy-bank-fill me-2 text-info"></i>Saldo Awal Pembukuan</h2>
    <p>Setup satu kali: modal awal pemilik berupa kas awal &amp; persediaan BBM awal per tangki. Setelah disimpan, data ini tidak bisa diinput ulang.</p>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form action="{{ route('saldo-awal.store') }}" method="POST" id="formSaldoAwal">
        @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Tanggal Saldo Awal</label>
                <input type="date" name="tanggal_saldo_awal" class="form-control"
                    value="{{ old('tanggal_saldo_awal', date('Y-m-d')) }}" required>
                @error('tanggal_saldo_awal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Kas Awal (Rp)</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="text" id="kasAwalDisplay" class="form-control"
                        placeholder="cth: 50.000.000"
                        value="{{ old('kas_awal') ? number_format(old('kas_awal'), 0, ',', '.') : '' }}"
                        autocomplete="off">
                    <input type="hidden" name="kas_awal" id="kasAwalValue" value="{{ old('kas_awal', 0) }}">
                </div>
                @error('kas_awal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <hr class="my-4">

            <h6 class="fw-bold text-uppercase mb-3" style="letter-spacing:.05em;">
                <i class="bi bi-droplet-half me-2"></i>Persediaan BBM Awal per Tangki
            </h6>
            <p class="text-muted small">Kosongkan tangki yang belum ada stok fisiknya — baris jurnal &amp; stok hanya dibuat untuk tangki yang diisi.</p>

            @forelse($tangkis as $tangki)
            <div class="border rounded-3 p-3 mb-3">
                <div class="fw-semibold mb-2">{{ $tangki->nama_tangki }} <span class="text-muted small">({{ $tangki->masterBbm->jenis_bbm ?? '-' }})</span></div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small">Stok Awal (Liter)</label>
                        <input type="number" name="tangki[{{ $tangki->id }}][stok_awal]" class="form-control" min="0"
                            value="{{ old('tangki.'.$tangki->id.'.stok_awal') }}">
                        @error('tangki.'.$tangki->id.'.stok_awal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Harga Pokok Awal (Rp/Liter)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control harga-tangki-display"
                                placeholder="cth: 9.200"
                                data-target="hargaTangkiValue{{ $tangki->id }}"
                                value="{{ old('tangki.'.$tangki->id.'.harga_awal') ? number_format(old('tangki.'.$tangki->id.'.harga_awal'), 0, ',', '.') : '' }}"
                                autocomplete="off">
                            <input type="hidden" name="tangki[{{ $tangki->id }}][harga_awal]" id="hargaTangkiValue{{ $tangki->id }}"
                                value="{{ old('tangki.'.$tangki->id.'.harga_awal', 0) }}">
                        </div>
                        @error('tangki.'.$tangki->id.'.harga_awal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            @empty
            <p class="text-muted text-center">Belum ada tangki aktif.</p>
            @endforelse

            <div class="d-flex gap-2 mt-4">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-info text-white"><i class="bi bi-save me-1"></i>Simpan Saldo Awal</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function pasangFormatRupiah(display, hidden) {
    display.addEventListener('input', function () {
        let raw = this.value.replace(/\D/g, '');
        this.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        hidden.value = raw || 0;
    });
}

pasangFormatRupiah(document.getElementById('kasAwalDisplay'), document.getElementById('kasAwalValue'));

document.querySelectorAll('.harga-tangki-display').forEach(function (el) {
    pasangFormatRupiah(el, document.getElementById(el.dataset.target));
});

document.getElementById('formSaldoAwal').addEventListener('submit', function () {
    document.getElementById('kasAwalValue').value = document.getElementById('kasAwalDisplay').value.replace(/\D/g, '') || 0;
    document.querySelectorAll('.harga-tangki-display').forEach(function (el) {
        document.getElementById(el.dataset.target).value = el.value.replace(/\D/g, '') || 0;
    });
});
</script>
@endpush
