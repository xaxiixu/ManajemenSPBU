<?php

namespace App\Http\Controllers;

use App\Models\ShiftMaster;
use App\Models\User;
use Illuminate\Http\Request;

class JadwalShiftController extends Controller
{
    public function index()
    {
        $shiftMaster = ShiftMaster::orderBy('jam_mulai')->get();
        $petugas     = User::where('role', 'petugas')->orderBy('name')->get();

        return view('jadwal-shift.index', compact('shiftMaster', 'petugas'));
    }

    public function updateJamShift(Request $request, ShiftMaster $shiftMaster)
    {
        $validated = $request->validate([
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i',
        ]);

        $shiftMaster->update($validated);

        return redirect()->route('jadwal-shift.index')
            ->with('success', "Jam shift {$shiftMaster->shift} berhasil diupdate.");
    }

    public function updateShiftDefault(Request $request, User $petugas)
    {
        if ($petugas->role !== 'petugas') {
            abort(404);
        }

        $validated = $request->validate([
            'shift_default' => 'required|in:Pagi,Siang,Malam',
        ]);

        $petugas->update($validated);

        return redirect()->route('jadwal-shift.index')
            ->with('success', "Shift default {$petugas->name} berhasil diupdate.");
    }
}
