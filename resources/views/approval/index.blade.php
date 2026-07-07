@extends('layouts.app')
@section('title', 'Approval')
@section('page-title', 'Approval')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-check2-square me-2 text-danger"></i>Approval</h2>
    <p>Pengajuan shift & lembur yang menunggu persetujuan</p>
</div>

<div class="card mb-3">
    <div class="card-header">Pengajuan Shift ({{ $pengajuanShift->count() }})</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Petugas</th>
                    <th>Shift Diminta</th>
                    <th>Berlaku</th>
                    <th>Alasan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengajuanShift as $item)
                <tr>
                    <td><strong>{{ $item->user->name ?? 'Petugas dihapus' }}</strong></td>
                    <td><span class="badge bg-secondary">{{ $item->shift_diminta }}</span></td>
                    <td>{{ $item->tanggal_berlaku->format('d/m/Y') }}</td>
                    <td>{{ $item->alasan }}</td>
                    <td>
                        <form method="POST" action="{{ route('pengajuan-shift.approve', $item) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('pengajuan-shift.reject', $item) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Tolak</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada pengajuan shift yang pending.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">Pengajuan Lembur ({{ $lembur->count() }})</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Petugas</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Alasan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lembur as $item)
                <tr>
                    <td><strong>{{ $item->user->name ?? 'Petugas dihapus' }}</strong></td>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $item->jam_mulai }} - {{ $item->jam_selesai }}</td>
                    <td>{{ $item->alasan }}</td>
                    <td>
                        <form method="POST" action="{{ route('lembur.approve', $item) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('lembur.reject', $item) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Tolak</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada pengajuan lembur yang pending.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
