@extends('layouts.app')
@section('title', 'Jadwal Shift')
@section('page-title', 'Jadwal Shift')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-calendar-week me-2 text-danger"></i>Jadwal Shift</h2>
    <p>Atur jam shift dan shift default tiap petugas</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Jam Shift</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Shift</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shiftMaster as $sm)
                        <tr>
                            <form method="POST" action="{{ route('jadwal-shift.update-jam', $sm) }}">
                            @csrf @method('PUT')
                                <td class="align-middle"><span class="badge bg-secondary">{{ $sm->shift }}</span></td>
                                <td><input type="time" name="jam_mulai" class="form-control form-control-sm" value="{{ substr($sm->jam_mulai, 0, 5) }}" required></td>
                                <td><input type="time" name="jam_selesai" class="form-control form-control-sm" value="{{ substr($sm->jam_selesai, 0, 5) }}" required></td>
                                <td><button type="submit" class="btn btn-sm btn-danger">Simpan</button></td>
                            </form>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Shift Default Petugas</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Shift Default</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($petugas as $p)
                        <tr>
                            <form method="POST" action="{{ route('jadwal-shift.update-default', $p) }}">
                            @csrf @method('PUT')
                                <td class="align-middle">{{ $p->name }}</td>
                                <td>
                                    <select name="shift_default" class="form-select form-select-sm">
                                        @foreach(['Pagi', 'Siang', 'Malam'] as $s)
                                        <option value="{{ $s }}" {{ $p->shift_default == $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><button type="submit" class="btn btn-sm btn-danger">Simpan</button></td>
                            </form>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada petugas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
