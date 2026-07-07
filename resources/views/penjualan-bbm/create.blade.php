@extends('layouts.app')
@section('title', 'Tambah Penjualan BBM')
@section('page-title', 'Tambah Penjualan BBM')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-plus-circle me-2 text-danger"></i>Tambah Penjualan BBM</h2>
    <p>Input data penjualan satu shift</p>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Data belum tersimpan, periksa kembali:</strong>
    <ul class="mb-0 mt-1">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if ($tanggalOperasional['lintas_tengah_malam'])
<div class="alert alert-warning">
    <i class="bi bi-moon-stars-fill me-1"></i>
    Saat ini masih berlangsung <strong>Shift {{ $tanggalOperasional['shift_aktif'] }}</strong> yang dimulai kemarin
    (<strong>{{ $tanggalOperasional['tanggal']->format('d/m/Y') }}</strong>).
    Jika mencatat transaksi shift tersebut, pilih Tanggal <strong>{{ $tanggalOperasional['tanggal']->format('d/m/Y') }}</strong>, bukan hari ini.
</div>
@endif

<form action="{{ route('penjualan-bbm.store') }}" method="POST" enctype="multipart/form-data">
@csrf
<div class="row g-3">

    {{-- Kolom Kiri --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Informasi Shift</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggalInput" class="form-control"
                        value="{{ old('tanggal', date('Y-m-d')) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Shift</label>
                    <select name="shift" id="shiftSelect" class="form-select" required>
                        <option value="">-- Pilih Shift --</option>
                        @foreach(['Pagi', 'Siang', 'Malam'] as $s)
                        <option value="{{ $s }}" {{ old('shift') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Operator (Petugas Hadir)</label>
                    <select name="operator_id" id="operatorSelect" class="form-select" required disabled>
                        <option value="">-- Pilih tanggal & shift dulu --</option>
                    </select>
                    <small id="operatorHelp" class="text-muted"></small>
                    @error('operator_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenis BBM</label>
                    <select name="jenis_bbm" id="jenisBbmSelect" class="form-select" required>
                        <option value="">-- Pilih Jenis BBM --</option>
                        @foreach($jenisBbmOptions as $jb)
                        <option value="{{ $jb->jenis_bbm }}"
                            data-ron="{{ $jb->ron }}"
                            data-harga="{{ $jb->harga_per_liter }}"
                            {{ old('jenis_bbm') === $jb->jenis_bbm ? 'selected' : '' }}>
                            {{ $jb->jenis_bbm }}
                        </option>
                        @endforeach
                    </select>
                    @error('jenis_bbm')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold">RON</label>
                        <input type="text" id="ronDisplay" class="form-control bg-light" value="-" readonly tabindex="-1">
                        <input type="hidden" name="ron" id="ronValue">
                        <small class="text-muted">Otomatis mengikuti jenis BBM.</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold">Harga per Liter (Rp)</label>
                        <input type="text" id="hargaDisplay" class="form-control bg-light" value="-" readonly tabindex="-1">
                        <input type="hidden" name="harga_per_liter" id="hargaValue">
                        <small class="text-muted">Otomatis mengikuti jenis BBM.</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pulau</label>
                    <input type="text" name="pulau" class="form-control" placeholder="cth: 1" required
                        value="{{ old('pulau') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Data Meter & Foto</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Meter Awal</label>
                    <input type="number" name="meter_awal" class="form-control" id="meterAwal" placeholder="Angka meter awal"
                        value="{{ old('meter_awal') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Meter Akhir</label>
                    <input type="number" name="meter_akhir" class="form-control" id="meterAkhir" placeholder="Angka meter akhir"
                        value="{{ old('meter_akhir') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Foto Meter Awal</label>
                    <input type="file" name="foto_meter_awal" class="form-control" accept="image/*" required>
                    <small class="text-muted">Format JPG/PNG, maks 2MB</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Foto Meter Akhir</label>
                    <input type="file" name="foto_meter_akhir" class="form-control" accept="image/*" required>
                    <small class="text-muted">Format JPG/PNG, maks 2MB</small>
                </div>

                {{-- Preview kalkulasi --}}
                <div class="p-3 rounded" style="background:#f8f9fa; border: 1px dashed #dee2e6;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Liter Terjual:</span>
                        <strong id="previewLiter">— L</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Total Penjualan:</span>
                        <strong id="previewTotal" class="text-danger">— </strong>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label fw-semibold">Catatan (opsional)</label>
                    <textarea name="catatan" class="form-control" rows="2">{{ old('catatan') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="{{ route('penjualan-bbm.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan</button>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
    const tanggalInput   = document.getElementById('tanggalInput');
    const shiftSelect    = document.getElementById('shiftSelect');
    const operatorSelect = document.getElementById('operatorSelect');
    const operatorHelp   = document.getElementById('operatorHelp');
    const oldOperatorId  = '{{ old('operator_id') }}';

    async function muatOperator(preselectOld = false) {
        const tanggal = tanggalInput.value;
        const shift = shiftSelect.value;

        operatorHelp.textContent = '';
        operatorHelp.classList.remove('text-danger');

        if (!tanggal || !shift) {
            operatorSelect.innerHTML = '<option value="">-- Pilih tanggal & shift dulu --</option>';
            operatorSelect.disabled = true;
            return;
        }

        operatorSelect.innerHTML = '<option value="">Memuat...</option>';
        operatorSelect.disabled = true;

        try {
            const url = '{{ route('penjualan-bbm.operator-tersedia') }}?tanggal=' + encodeURIComponent(tanggal) + '&shift=' + encodeURIComponent(shift);
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const operators = await res.json();

            if (!operators.length) {
                operatorSelect.innerHTML = '<option value="">-- Tidak ada petugas hadir --</option>';
                operatorHelp.textContent = 'Belum ada petugas yang absen hadir di tanggal & shift ini. Penjualan hanya bisa diinput untuk petugas yang sudah tercatat hadir.';
                operatorHelp.classList.add('text-danger');
                operatorSelect.disabled = true;
                return;
            }

            operatorSelect.innerHTML = '<option value="">-- Pilih Operator --</option>' +
                operators.map(o => `<option value="${o.id}">${o.name}</option>`).join('');
            operatorSelect.disabled = false;

            if (preselectOld && oldOperatorId) {
                operatorSelect.value = oldOperatorId;
            }
        } catch (e) {
            operatorSelect.innerHTML = '<option value="">Gagal memuat data operator</option>';
            operatorSelect.disabled = true;
        }
    }

    tanggalInput.addEventListener('change', () => muatOperator(false));
    shiftSelect.addEventListener('change', () => muatOperator(false));

    if (tanggalInput.value && shiftSelect.value) {
        muatOperator(true);
    }

    // Auto-fill RON & harga per liter dari master_bbm saat jenis BBM dipilih.
    // Field ini readonly (bukan disabled) supaya nilainya tetap ikut ter-submit,
    // meskipun controller tetap mengambil ulang nilai resminya dari server.
    const jenisBbmSelect = document.getElementById('jenisBbmSelect');
    const ronDisplay      = document.getElementById('ronDisplay');
    const ronValue        = document.getElementById('ronValue');
    const hargaDisplay    = document.getElementById('hargaDisplay');
    const hargaValue      = document.getElementById('hargaValue');
    let hargaAktif = 0;

    function updateJenisBbm() {
        const opt = jenisBbmSelect.options[jenisBbmSelect.selectedIndex];

        if (opt && opt.value) {
            ronDisplay.value   = opt.dataset.ron || '-';
            ronValue.value     = opt.dataset.ron || '';
            hargaAktif          = parseFloat(opt.dataset.harga) || 0;
            hargaDisplay.value = 'Rp ' + hargaAktif.toLocaleString('id-ID');
            hargaValue.value   = hargaAktif;
        } else {
            ronDisplay.value   = '-';
            ronValue.value     = '';
            hargaAktif          = 0;
            hargaDisplay.value = '-';
            hargaValue.value   = '';
        }

        updatePreview();
    }

    jenisBbmSelect.addEventListener('change', updateJenisBbm);
    updateJenisBbm();

    function updatePreview() {
        const awal  = parseFloat(document.getElementById('meterAwal').value) || 0;
        const akhir = parseFloat(document.getElementById('meterAkhir').value) || 0;
        const liter = akhir - awal;
        const total = liter * hargaAktif;

        document.getElementById('previewLiter').textContent =
            liter > 0 ? liter.toLocaleString('id-ID') + ' L' : '— L';
        document.getElementById('previewTotal').textContent =
            total > 0 ? 'Rp ' + total.toLocaleString('id-ID') : '—';
    }

    document.getElementById('meterAwal').addEventListener('input', updatePreview);
    document.getElementById('meterAkhir').addEventListener('input', updatePreview);
</script>
@endpush
