@extends('layouts.app')
@section('title', 'Tambah Akun COA')
@section('page-title', 'Tambah Akun COA')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-plus-circle me-2 text-danger"></i>Tambah Akun COA</h2>
    <p>Tambah akun keuangan baru</p>
</div>

<div class="row g-3">
    <div class="col-md-7" style="max-width:600px;">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('coa.store') }}" method="POST" id="formCoa">
                @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent <span class="text-muted fw-normal">(opsional)</span></label>
                        <select name="parent_id" class="form-select" id="parentId">
                            <option value="">-- Tidak ada (akun standalone/parent) --</option>
                            @foreach($parents as $p)
                            <option value="{{ $p->id }}" data-kategori="{{ $p->kategori }}" data-kode="{{ $p->kode_akun }}"
                                {{ old('parent_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->kode_akun }} — {{ $p->nama_akun }}
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pilih parent kalau akun ini adalah child (misal sub-persediaan atau sub-beban). Kosongkan untuk akun standalone/parent baru.</small>
                        @error('parent_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="kategori" class="form-select" id="kategori">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="aset"       {{ old('kategori') == 'aset'       ? 'selected' : '' }}>Aset</option>
                            <option value="kewajiban"  {{ old('kategori') == 'kewajiban'  ? 'selected' : '' }}>Kewajiban</option>
                            <option value="modal"      {{ old('kategori') == 'modal'      ? 'selected' : '' }}>Modal</option>
                            <option value="pendapatan" {{ old('kategori') == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                            <option value="beban"      {{ old('kategori') == 'beban'      ? 'selected' : '' }}>Beban</option>
                        </select>
                        <small class="text-muted">Otomatis mengikuti kategori parent kalau parent dipilih.</small>
                        @error('kategori')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Akun</label>
                        <div class="input-group">
                            <span class="input-group-text fw-semibold" id="kodePrefix">-</span>
                            <input type="text" name="kode_suffix" id="kodeSuffix" class="form-control"
                                placeholder="cth: 105" value="{{ old('kode_suffix') }}"
                                required disabled inputmode="numeric" autocomplete="off">
                        </div>
                        <small class="text-muted">
                            Tanpa parent: kategori Beban + "105" &rarr; <code>5105</code>.
                            Dengan parent (misal <code>5102</code> Beban Operasional) + "4" &rarr; <code>5102-4</code>.
                        </small>
                        <div id="suffixHint" class="small mt-1"></div>
                        @error('kode_suffix')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Akun</label>
                        <input type="text" name="nama_akun" id="namaAkun" class="form-control"
                            placeholder="cth: Pendapatan Penjualan Dexlite"
                            value="{{ old('nama_akun') }}" required>
                        @error('nama_akun')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Posisi Normal</label>
                        <input type="text" id="posisiNormal" class="form-control bg-light" value="-" readonly tabindex="-1">
                        <small class="text-muted">Otomatis mengikuti kategori: Aset & Beban = Debit | Kewajiban, Modal & Pendapatan = Kredit</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" name="deskripsi" class="form-control" value="{{ old('deskripsi') }}">
                    </div>
                    <div class="mb-4" id="saldoAwalGroup" style="display:none;">
                        <label class="form-label fw-semibold">Saldo Awal (Rp) <span class="text-muted fw-normal">(opsional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="saldoAwalDisplay" class="form-control"
                                placeholder="cth: 5.000.000"
                                value="{{ old('saldo_awal') ? number_format(old('saldo_awal'), 0, ',', '.') : '' }}"
                                autocomplete="off">
                            <input type="hidden" name="saldo_awal" id="saldoAwalValue" value="{{ old('saldo_awal', 0) }}">
                        </div>
                        <small class="text-muted">Saldo saat akun ini dibuat (statis, historis). Kosongkan/0 kalau akun mulai dari saldo 0 — akan otomatis membuat 1 jurnal penyeimbang ke akun Modal Pemilik (3101).</small>
                        @error('saldo_awal')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('coa.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5" style="max-width:400px;">
        <div class="card">
            <div class="card-header">Preview Akun</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Kode Akun</dt>
                    <dd class="col-7"><code id="previewKode">-</code></dd>
                    <dt class="col-5">Nama Akun</dt>
                    <dd class="col-7" id="previewNama">-</dd>
                    <dt class="col-5">Kategori</dt>
                    <dd class="col-7" id="previewKategori">-</dd>
                    <dt class="col-5">Posisi Normal</dt>
                    <dd class="col-7" id="previewPosisi">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const KATEGORI_INFO = {
        aset:       { prefix: '1', posisi: 'Debit',  label: 'Aset' },
        kewajiban:  { prefix: '2', posisi: 'Kredit', label: 'Kewajiban' },
        modal:      { prefix: '3', posisi: 'Kredit', label: 'Modal' },
        pendapatan: { prefix: '4', posisi: 'Kredit', label: 'Pendapatan' },
        beban:      { prefix: '5', posisi: 'Debit',  label: 'Beban' },
    };

    // Suffix child yang sudah terpakai per parent_id (dikirim dari CoaController::create())
    const CHILDREN_BY_PARENT = @json($childrenByParent);

    const parentEl     = document.getElementById('parentId');
    const kategoriEl   = document.getElementById('kategori');
    const kodePrefixEl = document.getElementById('kodePrefix');
    const kodeSuffixEl = document.getElementById('kodeSuffix');
    const posisiEl     = document.getElementById('posisiNormal');
    const namaEl       = document.getElementById('namaAkun');
    const suffixHintEl = document.getElementById('suffixHint');

    const previewKode     = document.getElementById('previewKode');
    const previewNama     = document.getElementById('previewNama');
    const previewKategori = document.getElementById('previewKategori');
    const previewPosisi   = document.getElementById('previewPosisi');

    // Saldo Awal hanya relevan untuk akun neraca (aset/kewajiban/modal) -
    // akun pendapatan/beban tidak punya "saldo" statis untuk dicatat.
    const KATEGORI_BOLEH_SALDO_AWAL = ['aset', 'kewajiban', 'modal'];
    const saldoAwalGroupEl   = document.getElementById('saldoAwalGroup');
    const saldoAwalDisplayEl = document.getElementById('saldoAwalDisplay');
    const saldoAwalValueEl   = document.getElementById('saldoAwalValue');

    function updateSaldoAwalVisibility() {
        const parent = selectedParent();
        const kategoriEfektif = parent ? parent.kategori : kategoriEl.value;

        if (KATEGORI_BOLEH_SALDO_AWAL.includes(kategoriEfektif)) {
            saldoAwalGroupEl.style.display = '';
        } else {
            saldoAwalGroupEl.style.display = 'none';
            saldoAwalDisplayEl.value = '';
            saldoAwalValueEl.value = 0;
        }
    }

    function pasangFormatRupiah(display, hidden) {
        display.addEventListener('input', function () {
            let raw = this.value.replace(/\D/g, '');
            this.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            hidden.value = raw || 0;
        });
    }

    pasangFormatRupiah(saldoAwalDisplayEl, saldoAwalValueEl);

    function selectedParent() {
        const opt = parentEl.options[parentEl.selectedIndex];
        return opt && opt.value ? { kode: opt.dataset.kode, kategori: opt.dataset.kategori } : null;
    }

    function applyState() {
        const parent = selectedParent();

        if (parent) {
            kategoriEl.value    = parent.kategori;
            kategoriEl.disabled = true;
            kodePrefixEl.textContent = parent.kode + '-';
            kodeSuffixEl.disabled   = false;
            posisiEl.value = KATEGORI_INFO[parent.kategori] ? KATEGORI_INFO[parent.kategori].posisi : '-';
        } else {
            kategoriEl.disabled = false;
            const info = KATEGORI_INFO[kategoriEl.value];
            if (info) {
                kodePrefixEl.textContent = info.prefix;
                kodeSuffixEl.disabled   = false;
                posisiEl.value = info.posisi;
            } else {
                kodePrefixEl.textContent = '-';
                kodeSuffixEl.disabled   = true;
                posisiEl.value = '-';
            }
        }
    }

    function updateSuffixHint() {
        const parent = selectedParent();

        if (!parent) {
            suffixHintEl.textContent = '';
            kodeSuffixEl.placeholder = 'cth: 105';
            return;
        }

        const used = (CHILDREN_BY_PARENT[parentEl.value] || [])
            .map(Number)
            .filter(n => !isNaN(n))
            .sort((a, b) => a - b);

        let next = 1;
        while (used.includes(next)) next++;

        suffixHintEl.innerHTML = used.length
            ? 'Suffix terpakai: <strong>' + used.join(', ') + '</strong> — Suffix berikutnya tersedia: <strong>' + next + '</strong>'
            : 'Belum ada child untuk parent ini — Suffix berikutnya tersedia: <strong>' + next + '</strong>';

        kodeSuffixEl.placeholder = String(next);
    }

    function updatePreview() {
        const parent = selectedParent();
        const suffix = kodeSuffixEl.value.trim();
        const info   = KATEGORI_INFO[kategoriEl.value];

        if (parent && suffix) {
            previewKode.textContent = parent.kode + '-' + suffix;
        } else if (!parent && info && suffix) {
            previewKode.textContent = info.prefix + suffix;
        } else {
            previewKode.textContent = '-';
        }

        previewNama.textContent     = namaEl.value.trim() || '-';
        previewKategori.textContent = info ? info.label : '-';
        previewPosisi.textContent   = posisiEl.value || '-';
    }

    parentEl.addEventListener('change', function () {
        applyState();
        updateSuffixHint();
        updatePreview();
        updateSaldoAwalVisibility();
    });

    kategoriEl.addEventListener('change', function () {
        applyState();
        updatePreview();
        updateSaldoAwalVisibility();
    });

    kodeSuffixEl.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        updatePreview();
    });

    namaEl.addEventListener('input', updatePreview);

    // Repopulasi state saat form kembali karena error validasi
    applyState();
    updateSuffixHint();
    updatePreview();
    updateSaldoAwalVisibility();
});
</script>
@endpush
