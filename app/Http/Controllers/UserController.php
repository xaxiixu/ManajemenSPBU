<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $data = User::orderBy('name')->get();
        return view('users.index', compact('data'));
    }

    public function create()
    {
        $this->authorizeManager();
        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->authorizeManager();

        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'email'             => 'required|email|unique:users,email',
            'password'          => 'required|string|min:6|confirmed',
            'role'              => 'required|in:manager,pengawas,petugas',
            'nik'               => 'nullable|string|max:20|unique:users,nik',
            'no_hp'             => 'nullable|string|max:20',
            'shift_default'     => 'nullable|in:Pagi,Siang,Malam',
            // Gaji pokok & tanggal bergabung wajib khusus petugas (masuk payroll)
            'gaji_pokok'        => 'nullable|required_if:role,petugas|integer|min:0',
            'tanggal_bergabung' => 'nullable|required_if:role,petugas|date',
        ], [
            'password.confirmed'        => 'Konfirmasi password tidak cocok.',
            'email.unique'              => 'Email sudah dipakai.',
            'role.in'                   => 'Role tidak valid.',
            'gaji_pokok.required_if'    => 'Gaji pokok wajib diisi untuk petugas.',
            'tanggal_bergabung.required_if' => 'Tanggal bergabung wajib diisi untuk petugas.',
        ]);

        $isPetugas = $validated['role'] === 'petugas';

        User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'is_active'         => 1,
            'nik'               => $validated['nik'] ?? null,
            'jabatan'           => $isPetugas ? 'operator' : null,
            'no_hp'             => $validated['no_hp'] ?? null,
            'shift_default'     => $validated['shift_default'] ?? null,
            // Kolom payroll hanya diisi untuk petugas; role lain di-null-kan
            'gaji_pokok'        => $isPetugas ? $validated['gaji_pokok'] : null,
            'tanggal_bergabung' => $isPetugas ? $validated['tanggal_bergabung'] : null,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorizeManager();
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeManager();

        $request->validate([
            'name'              => 'required|string|max:100',
            'email'             => 'required|email|unique:users,email,' . $user->id,
            'role'              => 'required|in:manager,pengawas,petugas',
            'is_active'         => 'required|in:0,1',
            'password'          => 'nullable|string|min:6|confirmed',
            'nik'               => 'nullable|string|max:20|unique:users,nik,' . $user->id,
            'no_hp'             => 'nullable|string|max:20',
            'shift_default'     => 'nullable|in:Pagi,Siang,Malam',
            'gaji_pokok'        => 'nullable|required_if:role,petugas|integer|min:0',
            'tanggal_bergabung' => 'nullable|required_if:role,petugas|date',
        ], [
            'password.confirmed'            => 'Konfirmasi password tidak cocok.',
            'gaji_pokok.required_if'        => 'Gaji pokok wajib diisi untuk petugas.',
            'tanggal_bergabung.required_if' => 'Tanggal bergabung wajib diisi untuk petugas.',
        ]);

        $isPetugas = $request->role === 'petugas';

        $data = [
            'name'              => $request->name,
            'email'             => $request->email,
            'role'              => $request->role,
            'is_active'         => $request->is_active,
            'nik'               => $request->nik,
            'jabatan'           => $isPetugas ? 'operator' : null,
            'no_hp'             => $request->no_hp,
            'shift_default'     => $request->shift_default,
            // Kolom payroll hanya untuk petugas; role lain di-null-kan.
            // CATATAN: mengganti gaji_pokok/tanggal_bergabung TIDAK mengubah
            // payroll_details periode lama yang sudah di-snapshot (histori aman).
            'gaji_pokok'        => $isPetugas ? $request->gaji_pokok : null,
            'tanggal_bergabung' => $isPetugas ? $request->tanggal_bergabung : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        $this->authorizeManager();

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    // Helper: hanya manager yang bisa aksi write
    private function authorizeManager()
    {
        if (auth()->user()->role !== 'manager') {
            abort(403, 'Hanya manager yang dapat melakukan aksi ini.');
        }
    }
}