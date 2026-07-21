<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PenjualanBbmController;
use App\Http\Controllers\MasterBbmController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\JurnalUmumController;
use App\Http\Controllers\BukuBesarController;
use App\Http\Controllers\LaporanLabaRugiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\JadwalShiftController;
use App\Http\Controllers\PengajuanShiftController;
use App\Http\Controllers\LemburController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PengaturanGajiController;
use App\Http\Controllers\PayrollController;

// ── Auth ─────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')->name('logout');

// ── Authenticated ─────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        if (auth()->user()->role === 'petugas') {
            return redirect()->route('presensi.index');
        }
        return redirect()->route('dashboard');
    });

    // ── Notifikasi: semua role ─────────────────────────
    Route::get('/notifikasi/data', [NotificationController::class, 'data'])->name('notifikasi.data');
    Route::get('/notifikasi/{id}/buka', [NotificationController::class, 'buka'])->name('notifikasi.buka');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'bacaSemua'])->name('notifikasi.baca-semua');

    // ── Dashboard & Laporan: semua kecuali petugas ─────
    Route::middleware('role:manager,pengawas')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/laporan-laba-rugi', [LaporanLabaRugiController::class, 'index'])->name('laporan-laba-rugi.index');
    });

    // ── Petugas: presensi self-service ─────────────────
    Route::middleware('role:petugas')->group(function () {
        Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
        Route::post('/presensi/absen-masuk', [PresensiController::class, 'absenMasuk'])->name('presensi.absen-masuk');
        Route::post('/presensi/absen-keluar', [PresensiController::class, 'absenKeluar'])->name('presensi.absen-keluar');

        // Pengajuan shift & lembur: petugas ajukan + edit/batalkan milik sendiri
        // selama masih pending (ownership & status dicek di controller)
        Route::get('/pengajuan-shift', [PengajuanShiftController::class, 'index'])->name('pengajuan-shift.index');
        Route::post('/pengajuan-shift', [PengajuanShiftController::class, 'store'])->name('pengajuan-shift.store');
        Route::get('/pengajuan-shift/{pengajuanShift}/edit', [PengajuanShiftController::class, 'edit'])->name('pengajuan-shift.edit');
        Route::put('/pengajuan-shift/{pengajuanShift}', [PengajuanShiftController::class, 'update'])->name('pengajuan-shift.update');
        Route::delete('/pengajuan-shift/{pengajuanShift}', [PengajuanShiftController::class, 'destroy'])->name('pengajuan-shift.destroy');

        Route::get('/lembur', [LemburController::class, 'index'])->name('lembur.index');
        Route::post('/lembur', [LemburController::class, 'store'])->name('lembur.store');
        Route::get('/lembur/jam-mulai-tersedia', [LemburController::class, 'jamMulaiTersedia'])->name('lembur.jam-mulai-tersedia');
        Route::get('/lembur/{lembur}/edit', [LemburController::class, 'edit'])->name('lembur.edit');
        Route::put('/lembur/{lembur}', [LemburController::class, 'update'])->name('lembur.update');
        Route::delete('/lembur/{lembur}', [LemburController::class, 'destroy'])->name('lembur.destroy');
    });

    // ── Pengawas & IT: input + hapus penjualan BBM ────
    Route::middleware('role:pengawas')->group(function () {
        Route::get('/penjualan-bbm/create', [PenjualanBbmController::class, 'create'])->name('penjualan-bbm.create');
        Route::get('/penjualan-bbm/operator-tersedia', [PenjualanBbmController::class, 'operatorTersedia'])->name('penjualan-bbm.operator-tersedia');
        Route::post('/penjualan-bbm', [PenjualanBbmController::class, 'store'])->name('penjualan-bbm.store');
        Route::delete('/penjualan-bbm/{penjualanBbm}', [PenjualanBbmController::class, 'destroy'])->name('penjualan-bbm.destroy');
    });

    // ── Pengawas: input pengeluaran operasional ───────
    Route::middleware('role:pengawas')->group(function () {
        Route::resource('pengeluaran', PengeluaranController::class)->except(['index', 'show']);
    });

    // ── Lihat pengeluaran: pengawas, manager (bukan petugas) ─
    Route::middleware('role:pengawas,manager')->group(function () {
        Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('pengeluaran.index');
        Route::get('/pengeluaran/{pengeluaran}', [PengeluaranController::class, 'show'])->name('pengeluaran.show');
    });

    // ── Lihat penjualan BBM: pengawas, manager (bukan petugas) ─
    Route::middleware('role:pengawas,manager')->group(function () {
        Route::get('/penjualan-bbm', [PenjualanBbmController::class, 'index'])->name('penjualan-bbm.index');
        Route::get('/penjualan-bbm/{penjualanBbm}', [PenjualanBbmController::class, 'show'])->name('penjualan-bbm.show');
    });

    // ── Pengawas + Manager: data petugas, jadwal shift, approval ─
    Route::middleware('role:pengawas,manager')->group(function () {
        Route::get('/petugas', [PetugasController::class, 'index'])->name('petugas.index');
        Route::get('/petugas/{id}', [PetugasController::class, 'show'])->name('petugas.show');
        Route::post('/petugas/{id}/mark-absensi', [PetugasController::class, 'markAbsensi'])->name('petugas.mark-absensi');
        Route::put('/absensi/{absensi}', [PetugasController::class, 'updateAbsensi'])->name('absensi.update');
        Route::delete('/absensi/{absensi}', [PetugasController::class, 'destroyAbsensi'])->name('absensi.destroy');

        Route::get('/jadwal-shift', [JadwalShiftController::class, 'index'])->name('jadwal-shift.index');
        Route::put('/jadwal-shift/shift-master/{shiftMaster}', [JadwalShiftController::class, 'updateJamShift'])->name('jadwal-shift.update-jam');
        Route::put('/jadwal-shift/petugas/{petugas}', [JadwalShiftController::class, 'updateShiftDefault'])->name('jadwal-shift.update-default');

        Route::get('/approval', [ApprovalController::class, 'index'])->name('approval.index');
        Route::post('/pengajuan-shift/{pengajuanShift}/approve', [PengajuanShiftController::class, 'approve'])->name('pengajuan-shift.approve');
        Route::post('/pengajuan-shift/{pengajuanShift}/reject', [PengajuanShiftController::class, 'reject'])->name('pengajuan-shift.reject');
        Route::post('/lembur/{lembur}/approve', [LemburController::class, 'approve'])->name('lembur.approve');
        Route::post('/lembur/{lembur}/reject', [LemburController::class, 'reject'])->name('lembur.reject');
    });

    // ── Manager + IT: akuntansi & master data ─────────
    Route::middleware('role:manager')->group(function () {
        // COA full (manager & IT bisa tambah/edit/hapus)
        Route::resource('coa', CoaController::class);

        // Master BBM full (manager & IT bisa tambah/edit/hapus) - tanpa show,
        // tidak ada halaman detail terpisah untuk master data ini.
        Route::resource('master-bbm', MasterBbmController::class)->except(['show']);

        // Jurnal & Buku Besar read only
        Route::resource('jurnal-umum', JurnalUmumController::class)->only(['index', 'show']);
        Route::resource('buku-besar', BukuBesarController::class)->only(['index', 'show']);
    });

    // ── Manager only: pengaturan & manajemen user ─────
    Route::middleware('role:manager')->group(function () {
        // Manajemen User (termasuk buat akun petugas)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // ── Manager: Payroll (pengaturan gaji + penggajian) ───
    Route::middleware('role:manager')->group(function () {
        // Pengaturan gaji (parameter global payroll)
        Route::get('/pengaturan-gaji', [PengaturanGajiController::class, 'edit'])->name('pengaturan-gaji.edit');
        Route::put('/pengaturan-gaji', [PengaturanGajiController::class, 'update'])->name('pengaturan-gaji.update');

        // Penggajian (generate, review, kirim)
        Route::get('/penggajian', [PayrollController::class, 'index'])->name('penggajian.index');
        Route::post('/penggajian/generate', [PayrollController::class, 'generate'])->name('penggajian.generate');
        Route::get('/penggajian/{payrollRun}', [PayrollController::class, 'show'])->name('penggajian.show');
        Route::delete('/penggajian/{payrollRun}', [PayrollController::class, 'destroy'])->name('penggajian.destroy');
        Route::post('/penggajian/{payrollRun}/kirim', [PayrollController::class, 'kirim'])->name('penggajian.kirim');

        // Penyesuaian manual (hanya saat draft — dicek di controller)
        Route::post('/penggajian/detail/{detail}/penyesuaian', [PayrollController::class, 'storePenyesuaian'])->name('penggajian.penyesuaian.store');
        Route::delete('/penggajian/penyesuaian/{penyesuaian}', [PayrollController::class, 'destroyPenyesuaian'])->name('penggajian.penyesuaian.destroy');
    });

    // ── Petugas: slip gaji milik sendiri ──────────────────
    Route::middleware('role:petugas')->group(function () {
        Route::get('/gaji-saya', [PayrollController::class, 'gajiSaya'])->name('gaji-saya.index');
        Route::get('/gaji-saya/{payrollDetail}', [PayrollController::class, 'gajiSayaShow'])->name('gaji-saya.show');
    });
});
