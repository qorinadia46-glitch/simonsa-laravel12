<?php
use App\Http\Controllers\ImportSantriController;
use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PelanggaranController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PoinController;
use App\Http\Controllers\SantriController;
use App\Http\Controllers\ImportKategoriController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\TazirController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::guard()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,1');
});

Route::middleware(['auth', 'aktif'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        // Import Data Santri Excel
        Route::get('/santri/import', [ImportSantriController::class, 'form'])
            ->name('santri.import.form');
        Route::get('/santri/import/template', [ImportSantriController::class, 'template'])
            ->name('santri.import.template');
        Route::post('/santri/import', [ImportSantriController::class, 'import'])
            ->name('santri.import.proses');
        Route::get('/santri/tambah', [SantriController::class, 'create'])->name('santri.create');
        Route::post('/santri', [SantriController::class, 'store'])->name('santri.store');
        Route::get('/santri/{santri}/ubah', [SantriController::class, 'edit'])->name('santri.edit');
        Route::put('/santri/{santri}', [SantriController::class, 'update'])->name('santri.update');
        Route::delete('/santri/{santri}', [SantriController::class, 'destroy'])->name('santri.destroy');

        Route::get('/pelanggaran/{pelanggaran}/ubah', [PelanggaranController::class, 'edit'])->name('pelanggaran.edit');
        Route::put('/pelanggaran/{pelanggaran}', [PelanggaranController::class, 'update'])->name('pelanggaran.update');

        Route::get('/kategori/tambah', [KategoriController::class, 'create'])->name('kategori.create');
        Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
        Route::get('/kategori/{kategori}/ubah', [KategoriController::class, 'edit'])->name('kategori.edit');
        Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
        Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

        // Import Kategori Pelanggaran Excel
        Route::get('/kategori/import', [ImportKategoriController::class, 'form'])
            ->name('kategori.import.form');
        Route::post('/kategori/import', [ImportKategoriController::class, 'import'])
            ->name('kategori.import.proses');
        Route::get('/pengguna/tambah', [PenggunaController::class, 'create'])->name('pengguna.create');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::get('/pengguna/{pengguna}/ubah', [PenggunaController::class, 'edit'])->name('pengguna.edit');
        Route::put('/pengguna/{pengguna}', [PenggunaController::class, 'update'])->name('pengguna.update');
        Route::delete('/pengguna/{pengguna}', [PenggunaController::class, 'destroy'])->name('pengguna.destroy');

        Route::delete('/pelanggaran/{pelanggaran}', [PelanggaranController::class, 'destroy'])->name('pelanggaran.destroy');
        Route::delete('/surat-peringatan/{surat}', [SuratController::class, 'destroy'])->name('surat.destroy');
        Route::delete('/tazir/{tazir}', [TazirController::class, 'destroy'])->name('tazir.destroy');
        Route::get('/tazir/{tazir}/ubah', [TazirController::class, 'edit'])->name('tazir.edit');
        Route::put('/tazir/{tazir}', [TazirController::class, 'update'])->name('tazir.update');

        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
        Route::put('/pengaturan/profil', [PengaturanController::class, 'profil'])->name('pengaturan.profil');

        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
        Route::get('/backup/sql', [BackupController::class, 'sql'])->name('backup.sql');
        Route::get('/backup/{backup}/unduh', [BackupController::class, 'unduh'])->name('backup.unduh');
        Route::post('/backup/pulihkan', [BackupController::class, 'pulihkan'])->name('backup.pulihkan');
        Route::delete('/backup/{backup}', [BackupController::class, 'destroy'])->name('backup.destroy');
    });

    Route::middleware('role:admin,keamanan')->group(function () {
        Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');

        Route::get('/pelanggaran/tambah', [PelanggaranController::class, 'create'])->name('pelanggaran.create');
        Route::post('/pelanggaran', [PelanggaranController::class, 'store'])->name('pelanggaran.store');

        Route::get('/surat-peringatan/tambah', [SuratController::class, 'create'])->name('surat.create');
        Route::post('/surat-peringatan', [SuratController::class, 'store'])->name('surat.store');
        Route::get('/surat-peringatan/{surat}/ubah', [SuratController::class, 'edit'])->name('surat.edit');
        Route::put('/surat-peringatan/{surat}', [SuratController::class, 'update'])->name('surat.update');
        Route::get('/surat-peringatan/{surat}/cetak', [SuratController::class, 'cetak'])->name('surat.cetak');

        Route::get('/tazir/tambah', [TazirController::class, 'create'])->name('tazir.create');
        Route::post('/tazir', [TazirController::class, 'store'])->name('tazir.store');

        Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
        Route::post('/notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
    });

    Route::get('/santri', [SantriController::class, 'index'])->name('santri.index');
    Route::get('/santri/{santri}', [SantriController::class, 'show'])->name('santri.show');

    Route::get('/pelanggaran', [PelanggaranController::class, 'index'])->name('pelanggaran.index');
    Route::get('/riwayat', [PelanggaranController::class, 'riwayat'])->name('riwayat.index');
    Route::get('/pelanggaran/{pelanggaran}', [PelanggaranController::class, 'show'])->name('pelanggaran.show');

    Route::get('/poin', [PoinController::class, 'index'])->name('poin.index');
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');

    Route::middleware('role:admin,keamanan')->group(function () {
        Route::get('/surat-peringatan', [SuratController::class, 'index'])->name('surat.index');
        Route::get('/surat-peringatan/{surat}', [SuratController::class, 'show'])->name('surat.show');
        Route::get('/tazir', [TazirController::class, 'index'])->name('tazir.index');
        Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::get('/aktivitas-login', [AktivitasController::class, 'index'])->name('aktivitas.index');
        Route::post('/laporan/catat-cetak', [LaporanController::class, 'catatCetak'])->name('laporan.cetak');
        Route::get('/laporan/unduh', [LaporanController::class, 'unduh'])->name('laporan.unduh');
        Route::post('/laporan/unduh-pilihan', [LaporanController::class, 'unduhPilihan'])->name('laporan.unduh-pilihan');
    });
});
