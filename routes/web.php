<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PenjualanBbmController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\JurnalUmumController;
use App\Http\Controllers\BukuBesarController;
use App\Http\Controllers\LaporanLabaRugiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PetugasController;

// ── Auth ─────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')->name('logout');

// ── Authenticated ─────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));

    // ── Semua role ────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/laporan-laba-rugi', [LaporanLabaRugiController::class, 'index'])->name('laporan-laba-rugi.index');

    // ── Pengawas: input operasional ───────────────────
    Route::middleware('role:pengawas')->group(function () {
        Route::resource('presensi', PresensiController::class)->except(['index', 'show']);
        Route::resource('pengeluaran', PengeluaranController::class)->except(['index', 'show']);
        Route::resource('penjualan-bbm', PenjualanBbmController::class)->except(['index', 'show']);
    });

    // ── Semua role: lihat operasional ─────────────────
    Route::middleware('role:pengawas,manager')->group(function () {
        Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
        Route::get('/presensi/{presensi}', [PresensiController::class, 'show'])->name('presensi.show');
        Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('pengeluaran.index');
        Route::get('/pengeluaran/{pengeluaran}', [PengeluaranController::class, 'show'])->name('pengeluaran.show');
        Route::get('/penjualan-bbm', [PenjualanBbmController::class, 'index'])->name('penjualan-bbm.index');
        Route::get('/penjualan-bbm/{penjualanBbm}', [PenjualanBbmController::class, 'show'])->name('penjualan-bbm.show');
    });

    // ── Manager + IT: akuntansi & master data ─────────
    Route::middleware('role:manager')->group(function () {
        // COA full (manager & IT bisa tambah/edit/hapus)
        Route::resource('coa', CoaController::class);

        // Jurnal & Buku Besar read only
        Route::resource('jurnal-umum', JurnalUmumController::class)->only(['index', 'show']);
        Route::resource('buku-besar', BukuBesarController::class)->only(['index', 'show']);

        // Data Petugas: lihat saja
        Route::get('/petugas', [PetugasController::class, 'index'])->name('petugas.index');
        Route::get('/petugas/{id}', [PetugasController::class, 'show'])->name('petugas.show');
    });

    // ── IT only: pengaturan & manajemen user ──────────
    Route::middleware('role:it')->group(function () {
        // Manajemen User
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Data Petugas full
        Route::get('/petugas/create', [PetugasController::class, 'create'])->name('petugas.create');
        Route::post('/petugas', [PetugasController::class, 'store'])->name('petugas.store');
        Route::get('/petugas/{id}/edit', [PetugasController::class, 'edit'])->name('petugas.edit');
        Route::put('/petugas/{id}', [PetugasController::class, 'update'])->name('petugas.update');
        Route::delete('/petugas/{id}', [PetugasController::class, 'destroy'])->name('petugas.destroy');
    });
});