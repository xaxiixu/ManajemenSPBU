@extends('layouts.app')
@section('title', 'Detail Payroll')
@section('page-title', 'Detail Payroll')

@section('content')
@php $isDraft = $payrollRun->isDraft(); @endphp

{{-- Header aksi (disembunyikan saat print) --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 d-print-none">
    <div>
        <a href="{{ route('penggajian.index') }}" class="btn btn-sm btn-link text-decoration-none ps-0">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <h2 class="fw-bold mb-0" style="font-size:1.3rem;">
            Payroll {{ $payrollRun->periode_mulai->translatedFormat('d M Y') }} — {{ $payrollRun->periode_selesai->translatedFormat('d M Y') }}
        </h2>
        <div class="small mt-1">
            @if($isDraft)
                <span class="badge bg-warning text-dark">Draft</span>
                <span class="text-muted">Dibuat oleh {{ $payrollRun->dibuatOleh->name ?? '—' }}</span>
            @else
                <span class="badge bg-success">Dikirim</span>
                <span class="text-muted">
                    oleh {{ $payrollRun->dikirimOleh->name ?? '—' }}
                    @if($payrollRun->dikirim_pada) pada {{ $payrollRun->dikirim_pada->translatedFormat('d M Y H:i') }} @endif
                </span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        @if(!$isDraft && $jurnal)
            <a href="{{ route('jurnal-umum.show', $jurnal) }}" class="btn btn-outline-primary">
                <i class="bi bi-journal-text me-1"></i>Lihat Jurnal Terkait
            </a>
        @endif
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Print / Export
        </button>
        @if($isDraft)
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapusDraft">
                <i class="bi bi-trash me-1"></i>Hapus Draft
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalKirim">
                <i class="bi bi-send-fill me-1"></i>Kirim Slip Gaji
            </button>
        @endif
    </div>
</div>

{{-- Header print --}}
<div class="d-none d-print-block text-center mb-4">
    <h4 class="mb-0 fw-bold">SPBU Management System</h4>
    <h5>Rekap Penggajian Petugas</h5>
    <p class="mb-0 text-muted">Periode: {{ $payrollRun->periode_mulai->translatedFormat('d M Y') }} — {{ $payrollRun->periode_selesai->translatedFormat('d M Y') }}</p>
    <hr>
</div>

@if($isDraft)
<div class="alert alert-warning d-flex align-items-center gap-2 d-print-none">
    <i class="bi bi-info-circle-fill"></i>
    <div>Ini masih <strong>draft</strong>. Kamu bisa menambah penyesuaian manual per petugas. Angka baru final setelah <strong>Kirim Slip Gaji</strong> (aksi terkunci & tidak bisa dibatalkan).</div>
</div>
@endif

{{-- Peringatan hari tidak tercatat: tanggal kerja tanpa record absensi sama sekali. --}}
@php $petugasTidakTercatat = $payrollRun->details->where('jumlah_tidak_tercatat', '>', 0); @endphp
@if($petugasTidakTercatat->count())
<div class="alert alert-warning d-print-none">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <strong>Ada hari kerja yang tidak tercatat kehadirannya — mohon verifikasi sebelum kirim final.</strong>
    </div>
    <p class="small mb-2">
        Tanggal berikut belum punya catatan absensi sama sekali (bukan hadir/sakit/izin/alpha).
        Untuk pengaman, hari-hari ini <strong>otomatis dipotong setara alpha</strong> pada draft ini.
        @if($isDraft)
            Jika sebenarnya petugas hadir/sakit/izin, catat dulu status yang benar di
            <strong>Data Petugas</strong>, lalu <strong>Generate Ulang</strong> draft periode ini.
        @endif
    </p>
    <ul class="small mb-0">
        @foreach($petugasTidakTercatat as $d)
            <li>
                <strong>{{ $d->user->name ?? 'User dihapus' }}</strong> —
                <span class="badge bg-warning text-dark">{{ $d->jumlah_tidak_tercatat }} hari tidak tercatat</span>
            </li>
        @endforeach
    </ul>
</div>
@endif

{{-- Tabel rincian --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Petugas</th>
                        <th class="text-end">Gaji Pokok</th>
                        <th class="text-center">Hadir</th>
                        <th class="text-end">Pot. Telat</th>
                        <th class="text-center">A / S / I</th>
                        <th class="text-end">Pot. Absen</th>
                        <th class="text-center">Lembur</th>
                        <th class="text-end">Uang Lembur</th>
                        <th class="text-end">Penyesuaian</th>
                        <th class="text-end">Gaji Bersih</th>
                        @if($isDraft)<th class="text-end pe-3 d-print-none">Aksi</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrollRun->details as $d)
                    <tr id="detail-{{ $d->id }}">
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $d->user->name ?? 'User dihapus' }}</div>
                            @if($d->gaji_pokok_prorate < ($d->user->gaji_pokok ?? 0))
                                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.65rem;">prorate</span>
                            @endif
                            @if($d->jumlah_tidak_tercatat > 0)
                                <span class="badge bg-warning text-dark" style="font-size:.65rem;"
                                    title="{{ $d->jumlah_tidak_tercatat }} hari tanpa catatan absensi — dipotong setara alpha, harap diverifikasi">
                                    <i class="bi bi-exclamation-triangle-fill"></i> {{ $d->jumlah_tidak_tercatat }} hari tidak tercatat
                                </span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($d->gaji_pokok_prorate) }}</td>
                        <td class="text-center">{{ $d->jumlah_hadir }}</td>
                        <td class="text-end {{ $d->potongan_telat > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $d->potongan_telat > 0 ? '−'.number_format($d->potongan_telat) : '0' }}
                            @if($d->jumlah_kali_telat > 0)<div style="font-size:.68rem;" class="text-muted">{{ $d->jumlah_kali_telat }}× / {{ $d->total_menit_telat_kena_potong }}m</div>@endif
                        </td>
                        <td class="text-center">{{ $d->jumlah_alpha }}/{{ $d->jumlah_sakit }}/{{ $d->jumlah_izin }}</td>
                        <td class="text-end {{ $d->potongan_absen > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $d->potongan_absen > 0 ? '−'.number_format($d->potongan_absen) : '0' }}
                        </td>
                        <td class="text-center">{{ $d->jumlah_jam_lembur }} jam</td>
                        <td class="text-end {{ $d->uang_lembur > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $d->uang_lembur > 0 ? '+'.number_format($d->uang_lembur) : '0' }}
                        </td>
                        <td class="text-end {{ $d->total_penyesuaian != 0 ? ($d->total_penyesuaian > 0 ? 'text-success' : 'text-danger') : 'text-muted' }}">
                            {{ $d->total_penyesuaian != 0 ? ($d->total_penyesuaian > 0 ? '+' : '−').number_format(abs($d->total_penyesuaian)) : '0' }}
                            @if($d->penyesuaian->count())<div style="font-size:.68rem;" class="text-muted">{{ $d->penyesuaian->count() }} item</div>@endif
                        </td>
                        <td class="text-end fw-bold">Rp {{ number_format($d->total_gaji_bersih) }}</td>
                        @if($isDraft)
                        <td class="text-end pe-3 d-print-none">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#modalPenyesuaian-{{ $d->id }}">
                                <i class="bi bi-plus-slash-minus"></i>
                            </button>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ $isDraft ? 11 : 10 }}" class="text-center text-muted py-4">Tidak ada petugas dalam periode ini.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3">TOTAL ({{ $payrollRun->details->count() }} petugas)</td>
                        <td class="text-end">{{ number_format($rekap['gaji_pokok']) }}</td>
                        <td></td>
                        <td colspan="3" class="text-end text-danger">−{{ number_format($rekap['potongan']) }}</td>
                        <td></td>
                        <td class="text-end text-success">+{{ number_format($rekap['lembur']) }}</td>
                        <td class="text-end {{ $rekap['penyesuaian'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $rekap['penyesuaian'] >= 0 ? '+' : '−' }}{{ number_format(abs($rekap['penyesuaian'])) }}
                        </td>
                        <td class="text-end">Rp {{ number_format($rekap['bersih']) }}</td>
                        @if($isDraft)<td class="d-print-none"></td>@endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<p class="text-muted small mt-2">Keterangan kolom: <strong>A/S/I</strong> = Alpha / Sakit / Izin. Pot. = Potongan.</p>

{{-- Modal penyesuaian per petugas (hanya draft) --}}
@if($isDraft)
    @foreach($payrollRun->details as $d)
    <div class="modal fade" id="modalPenyesuaian-{{ $d->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Penyesuaian — {{ $d->user->name ?? '—' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Ringkasan --}}
                    <div class="row g-2 mb-3 small">
                        <div class="col-6 col-md-3"><div class="text-muted">Subtotal</div><div class="fw-semibold">Rp {{ number_format($d->subtotal) }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted">Penyesuaian</div><div class="fw-semibold {{ $d->total_penyesuaian < 0 ? 'text-danger' : 'text-success' }}">{{ $d->total_penyesuaian >= 0 ? '+' : '−' }}Rp {{ number_format(abs($d->total_penyesuaian)) }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted">Gaji Bersih</div><div class="fw-bold">Rp {{ number_format($d->total_gaji_bersih) }}</div></div>
                    </div>

                    {{-- Daftar penyesuaian --}}
                    <table class="table table-sm align-middle">
                        <thead class="table-light"><tr><th>Keterangan</th><th class="text-end">Jumlah</th><th></th></tr></thead>
                        <tbody>
                            @forelse($d->penyesuaian as $p)
                            <tr>
                                <td>{{ $p->keterangan }}</td>
                                <td class="text-end {{ $p->jumlah < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $p->jumlah >= 0 ? '+' : '−' }}Rp {{ number_format(abs($p->jumlah)) }}
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('penggajian.penyesuaian.destroy', $p) }}" method="POST"
                                        onsubmit="return confirm('Hapus penyesuaian ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted">Belum ada penyesuaian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- Form tambah --}}
                    <hr>
                    <form action="{{ route('penggajian.penyesuaian.store', $d) }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Keterangan</label>
                            <input type="text" name="keterangan" class="form-control form-control-sm" maxlength="255" required
                                placeholder="mis. Bonus kinerja / Potongan kasbon">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tipe</label>
                            <select name="tipe" class="form-select form-select-sm">
                                <option value="bonus">Bonus (+)</option>
                                <option value="potongan">Potongan (−)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nominal</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="nominal" class="form-control" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-sm btn-danger w-100"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Modal kirim --}}
    <div class="modal fade" id="modalKirim" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('penggajian.kirim', $payrollRun) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Kirim Slip Gaji?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Slip gaji periode <strong>{{ $payrollRun->periode_mulai->translatedFormat('d M Y') }} — {{ $payrollRun->periode_selesai->translatedFormat('d M Y') }}</strong> akan diterbitkan ke {{ $payrollRun->details->count() }} petugas.</p>
                        <div class="alert alert-danger small mb-0">
                            <strong>Aksi ini final dan tidak bisa dibatalkan.</strong> Setelah dikirim, seluruh angka & penyesuaian
                            terkunci dan slip menjadi read-only. Total gaji bersih dibayarkan: <strong>Rp {{ number_format($rekap['bersih']) }}</strong>.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-send-fill me-1"></i>Ya, Kirim Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal hapus draft --}}
    <div class="modal fade" id="modalHapusDraft" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('penggajian.destroy', $payrollRun) }}" method="POST">
                    @csrf @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Hapus Draft?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Draft ini beserta seluruh perhitungan & penyesuaian-nya akan dihapus. Periode bisa di-generate ulang kapan saja.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@push('styles')
<style>
@media print {
    #sidebar, #topbar, .d-print-none { display: none !important; }
    #main-content { margin: 0 !important; padding: 1rem !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    table { font-size: .75rem !important; }
}
</style>
@endpush

@push('scripts')
<script>
// Buka lagi modal penyesuaian setelah redirect (?edit=<detailId>)
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const editId = params.get('edit');
    if (editId) {
        const el = document.getElementById('modalPenyesuaian-' + editId);
        if (el) new bootstrap.Modal(el).show();
    }
});
</script>
@endpush
@endsection
