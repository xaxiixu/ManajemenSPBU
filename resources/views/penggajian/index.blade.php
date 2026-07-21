@extends('layouts.app')
@section('title', 'Penggajian')
@section('page-title', 'Penggajian')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-cash-coin me-2 text-danger"></i>Penggajian</h2>
    <p>Generate, review, dan kirim slip gaji petugas per periode. Tanggal gajian saat ini: <strong>tiap tanggal {{ $setting->tanggal_gajian }}</strong>.</p>
</div>

@php
    // Periode paling atas = periode berjalan (menuju gajian berikutnya)
    $periodeIni = $kandidat[0] ?? null;
@endphp

{{-- Periode berjalan --}}
@if($periodeIni)
<div class="card mb-4" style="border-left:4px solid var(--spbu-red);">
    <div class="card-body d-flex flex-wrap align-items-center gap-3 justify-content-between">
        <div>
            <div class="text-muted small text-uppercase" style="letter-spacing:.05em;">Periode Berjalan</div>
            <div class="fw-bold fs-5">
                {{ $periodeIni['mulai']->translatedFormat('d M Y') }} — {{ $periodeIni['selesai']->translatedFormat('d M Y') }}
            </div>
            <div class="small mt-1">
                @php $run = $periodeIni['run']; @endphp
                @if(!$run)
                    <span class="badge bg-secondary">Belum di-generate</span>
                @elseif($run->isDraft())
                    <span class="badge bg-warning text-dark">Draft</span>
                @else
                    <span class="badge bg-success">Sudah dikirim</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @php $run = $periodeIni['run']; @endphp
            @if($run)
                <a href="{{ route('penggajian.show', $run) }}" class="btn btn-outline-danger">
                    <i class="bi bi-eye me-1"></i>Lihat Detail
                </a>
            @endif
            @if(!$run || $run->isDraft())
                <button type="button" class="btn btn-danger"
                    data-bs-toggle="modal" data-bs-target="#modalGenerate"
                    data-mulai="{{ $periodeIni['mulai']->toDateString() }}"
                    data-selesai="{{ $periodeIni['selesai']->toDateString() }}"
                    data-label="{{ $periodeIni['mulai']->translatedFormat('d M Y') }} — {{ $periodeIni['selesai']->translatedFormat('d M Y') }}"
                    data-regenerate="{{ $run ? '1' : '0' }}">
                    <i class="bi bi-arrow-repeat me-1"></i>{{ $run ? 'Regenerate Draft' : 'Generate Draft' }}
                </button>
            @endif
        </div>
    </div>
</div>
@endif

{{-- Daftar periode (termasuk yang terlewat) --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history me-1"></i>Riwayat & Periode Sebelumnya</span>
        <small class="text-muted">Periode yang belum pernah dikirim tetap bisa di-generate</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Periode</th>
                        <th>Status</th>
                        <th>Dibuat / Dikirim</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kandidat as $i => $k)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $k['mulai']->translatedFormat('d M Y') }} — {{ $k['selesai']->translatedFormat('d M Y') }}</div>
                            @if($i === 0)<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.7rem;">Berjalan</span>@endif
                        </td>
                        <td>
                            @php $run = $k['run']; @endphp
                            @if(!$run)
                                <span class="badge bg-secondary">Belum dibuat</span>
                            @elseif($run->isDraft())
                                <span class="badge bg-warning text-dark">Draft</span>
                            @else
                                <span class="badge bg-success">Dikirim</span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            @if($run && $run->isDikirim() && $run->dikirim_pada)
                                {{ $run->dikirim_pada->translatedFormat('d M Y H:i') }}
                            @elseif($run)
                                {{ $run->created_at?->translatedFormat('d M Y H:i') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            @php $run = $k['run']; @endphp
                            @if($run)
                                <a href="{{ route('penggajian.show', $run) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i> Lihat
                                </a>
                            @endif
                            @if(!$run || $run->isDraft())
                                <button type="button" class="btn btn-sm btn-danger"
                                    data-bs-toggle="modal" data-bs-target="#modalGenerate"
                                    data-mulai="{{ $k['mulai']->toDateString() }}"
                                    data-selesai="{{ $k['selesai']->toDateString() }}"
                                    data-label="{{ $k['mulai']->translatedFormat('d M Y') }} — {{ $k['selesai']->translatedFormat('d M Y') }}"
                                    data-regenerate="{{ $run ? '1' : '0' }}">
                                    <i class="bi bi-{{ $run ? 'arrow-repeat' : 'plus-lg' }}"></i> {{ $run ? 'Regenerate' : 'Generate' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal konfirmasi generate --}}
<div class="modal fade" id="modalGenerate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('penggajian.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="periode_mulai" id="genMulai">
                <input type="hidden" name="periode_selesai" id="genSelesai">
                <div class="modal-header">
                    <h5 class="modal-title" id="genTitle">Generate Draft Payroll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Periode: <strong id="genLabel"></strong></p>
                    <p class="mb-0 small text-muted">
                        Sistem akan menghitung gaji semua petugas <strong>aktif</strong> untuk periode ini
                        (gaji pokok, potongan telat/absen, lembur approved).
                    </p>
                    <div id="genWarnRegenerate" class="alert alert-warning small mt-3 mb-0 d-none">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Draft untuk periode ini sudah ada. Regenerate akan <strong>menghitung ulang dari nol</strong> dan
                        <strong>menghapus semua penyesuaian manual</strong> yang sudah dibuat pada draft tersebut.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-play-fill me-1"></i>Ya, Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const modalGenerate = document.getElementById('modalGenerate');
modalGenerate.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('genMulai').value   = btn.getAttribute('data-mulai');
    document.getElementById('genSelesai').value = btn.getAttribute('data-selesai');
    document.getElementById('genLabel').textContent = btn.getAttribute('data-label');

    const isRegen = btn.getAttribute('data-regenerate') === '1';
    document.getElementById('genTitle').textContent = isRegen ? 'Regenerate Draft Payroll' : 'Generate Draft Payroll';
    document.getElementById('genWarnRegenerate').classList.toggle('d-none', !isRegen);
});
</script>
@endpush
@endsection
