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
        $this->authorizeIT();
        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->authorizeIT();

        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:manager,pengawas,it',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'email.unique'       => 'Email sudah dipakai.',
            'role.in'            => 'Role tidak valid.',
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => 1,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorizeIT();
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeIT();

        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'role'      => 'required|in:manager,pengawas,it',
            'is_active' => 'required|in:0,1',
            'password'  => 'nullable|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'is_active' => $request->is_active,
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
        $this->authorizeIT();

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    // Helper: hanya IT yang bisa aksi write
    private function authorizeIT()
    {
        if (auth()->user()->role !== 'it') {
            abort(403, 'Hanya IT yang dapat melakukan aksi ini.');
        }
    }
}