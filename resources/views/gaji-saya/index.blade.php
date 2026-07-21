@extends('layouts.app')
@section('title', 'Gaji Saya')
@section('page-title', 'Gaji Saya')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-wallet2 me-2 text-danger"></i>Gaji Saya</h2>
    <p>Riwayat slip gaji yang sudah diterbitkan untuk Anda.</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Periode</th>
                        <th class="text-end">Gaji Pokok</th>
                        <th class="text-end">Potongan</th>
                        <th class="text-end">Lembur</th>
                        <th class="text-end">Gaji Bersih</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slip as $d)
                    <tr>
                        <td class="ps-3 fw-semibold">
                            {{ $d->payrollRun->periode_mulai->translatedFormat('d M Y') }} — {{ $d->payrollRun->periode_selesai->translatedFormat('d M Y') }}
                        </td>
                        <td class="text-end">{{ number_format($d->gaji_pokok_prorate) }}</td>
                        <td class="text-end text-danger">−{{ number_format($d->potongan_telat + $d->potongan_absen) }}</td>
                        <td class="text-end text-success">+{{ number_format($d->uang_lembur) }}</td>
                        <td class="text-end fw-bold">Rp {{ number_format($d->total_gaji_bersih) }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('gaji-saya.show', $d) }}" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-file-earmark-text"></i> Lihat Slip
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada slip gaji yang diterbitkan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
