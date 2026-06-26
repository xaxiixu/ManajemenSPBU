@extends('layouts.app')
@section('title', 'Penjualan BBM')
@section('page-title', 'Penjualan BBM')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-fuel-pump me-2 text-danger"></i>Penjualan BBM</h2>
        <p>Data penjualan bahan bakar minyak</p>
    </div>
    @if(in_array(auth()->user()->role, ['pengawas', 'it']))
    <a href="{{ route('penjualan-bbm.create') }}" class="btn btn-danger">
        <i class="bi bi-plus-lg me-1"></i> Tambah Data
    </a>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Shift</th>
                    <th>Pulau/Nozzle</th>
                    <th>Jenis BBM</th>
                    <th>Liter Terjual</th>
                    <th>Total Penjualan</th>
                    @if(in_array(auth()->user()->role, ['pengawas', 'it']))
                    <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td><span class="badge bg-secondary">{{ $item->shift }}</span></td>
                    <td>P{{ $item->pulau }} / {{ $item->nozzle }}</td>
                    <td>{{ $item->jenis_bbm }}</td>
                    <td>{{ number_format($item->liter_terjual) }} L</td>
                    <td>Rp {{ number_format($item->total_penjualan) }}</td>
                    @if(in_array(auth()->user()->role, ['pengawas', 'it']))
                    <td>
                        <a href="{{ route('penjualan-bbm.show', $item) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                        <form action="{{ route('penjualan-bbm.destroy', $item) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Hapus data ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data penjualan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($data->hasPages())
    <div class="card-footer">{{ $data->links() }}</div>
    @endif
</div>
@endsection