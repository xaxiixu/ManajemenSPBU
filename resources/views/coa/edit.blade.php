@extends('layouts.app')
@section('title', 'Edit Akun COA')
@section('page-title', 'Edit Akun COA')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil me-2 text-danger"></i>Edit Akun COA</h2>
    <p>Update akun {{ $coa->kode_akun }} — {{ $coa->nama_akun }}</p>
</div>

<div class="row g-3">
    <div class="col-md-7" style="max-width:600px;">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('coa.update', $coa) }}" method="POST" id="formCoa">
                @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent <span class="text-muted fw-normal">(opsional)</span></label>
                        <select name="parent_id" class="form-select" id="parentId" {{ $hasChildren ? 'disabled' : '' }}>
                            <option value="">-- Tidak ada (akun standalone/parent) --</option>
                            @foreach($parents as $p)
                            <option value="{{ $p->id }}" data-kategori="{{ $p->kategori }}" data-kode="{{ $p->kode_akun }}"
                                {{ old('parent_id', $coa->parent_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->kode_akun }} — {{ $p->nama_akun }}
                            </option>
                            @endforeach
                        </select>
                        @if($hasChildren)
                        <small class="text-muted">Akun ini punya akun anak, jadi tidak bisa dijadikan child akun lain.</small>
                        @else
                        <small class="text-muted">Pilih parent kalau akun ini adalah child. Kosongkan untuk akun standalone/parent.</small>
                        @endif
                        @error('parent_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="kategori" class="form-select" id="kategori">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="aset"       {{ old('kategori', $coa->kategori) == 'aset'       ? 'selected' : '' }}>Aset</option>
                            <option value="kewajiban"  {{ old('kategori', $coa->kategori) == 'kewajiban'  ? 'selected' : '' }}>Kewajiban</option>
                            <option value="modal"      {{ old('kategori', $coa->kategori) == 'modal'      ? 'selected' : '' }}>Modal</option>
                            <option value="pendapatan" {{ old('kategori', $coa->kategori) == 'pendapatan' ? 'selected' : '' }}>Pendapatan</option>
                            <option value="beban"      {{ old('kategori', $coa->kategori) == 'beban'      ? 'selected' : '' }}>Beban</option>
                        </select>
                        <small class="text-muted">Otomatis mengikuti kategori parent kalau parent dipilih.</small>
                        @error('kategori')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Akun</label>
                        <div class="input-group">
                            <span class="input-group-text fw-semibold" id="kodePrefix">-</span>
                            <input type="text" name="kode_suffix" id="kodeSuffix" class="form-control"
                                value="{{ old('kode_suffix', $kodeSuffix) }}"
                                required inputmode="numeric" autocomplete="off">
                        </div>
                        <small class="text-muted">
                            Tanpa parent: kategori Beban + "105" &rarr; <code>5105</code>.
                            Dengan parent (misal <code>5102</code> Beban Operasional) + "4" &rarr; <code>5102-4</code>.
                        </small>
                        @error('kode_suffix')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Akun</label>
                        <input type="text" name="nama_akun" id="namaAkun" class="form-control"
                            value="{{ old('nama_akun', $coa->nama_akun) }}" required>
                        @error('nama_akun')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Posisi Normal</label>
                        <input type="text" id="posisiNormal" class="form-control bg-light" value="-" readonly tabindex="-1">
                        <small class="text-muted">Otomatis mengikuti kategori: Aset & Beban = Debit | Kewajiban, Modal & Pendapatan = Kredit</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <input type="text" name="deskripsi" class="form-control"
                            value="{{ old('deskripsi', $coa->deskripsi) }}">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="is_aktif" class="form-select" required>
                            <option value="1" {{ old('is_aktif', $coa->is_aktif) ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ !old('is_aktif', $coa->is_aktif) ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('coa.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-save me-1"></i>Update</button>
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

    const parentEl     = document.getElementById('parentId');
    const kategoriEl   = document.getElementById('kategori');
    const kodePrefixEl = document.getElementById('kodePrefix');
    const kodeSuffixEl = document.getElementById('kodeSuffix');
    const posisiEl     = document.getElementById('posisiNormal');
    const namaEl       = document.getElementById('namaAkun');

    const previewKode     = document.getElementById('previewKode');
    const previewNama     = document.getElementById('previewNama');
    const previewKategori = document.getElementById('previewKategori');
    const previewPosisi   = document.getElementById('previewPosisi');

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
            posisiEl.value = KATEGORI_INFO[parent.kategori] ? KATEGORI_INFO[parent.kategori].posisi : '-';
        } else {
            kategoriEl.disabled = false;
            const info = KATEGORI_INFO[kategoriEl.value];
            if (info) {
                kodePrefixEl.textContent = info.prefix;
                posisiEl.value = info.posisi;
            } else {
                kodePrefixEl.textContent = '-';
                posisiEl.value = '-';
            }
        }
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
        updatePreview();
    });

    kategoriEl.addEventListener('change', function () {
        applyState();
        updatePreview();
    });

    kodeSuffixEl.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        updatePreview();
    });

    namaEl.addEventListener('input', updatePreview);

    applyState();
    updatePreview();
});
</script>
@endpush
