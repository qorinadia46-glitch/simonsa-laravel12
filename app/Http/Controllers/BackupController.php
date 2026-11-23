<?php

namespace App\Http\Controllers;

use App\Models\LogBackup;
use App\Models\PengaturanSistem;
use App\Services\BackupService;
use App\Support\Validasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BackupController extends Controller
{
    public function index()
    {
        $data = LogBackup::with('pengguna')->orderByDesc('waktu_backup')->orderByDesc('id_backup')->paginate(10);
        $jadwal = PengaturanSistem::ambil('jadwal_backup', '00:00');

        return view('backup.index', compact('data', 'jadwal'));
    }

    public function store(BackupService $backup)
    {
        $log = $backup->buat('manual', auth()->id());
        if (! $log || $log->status_backup === 'gagal') {
            return back()->with('gagal', $log->keterangan ?? 'Backup gagal dibuat.');
        }

        return back()->with('sukses', 'Backup berhasil dibuat: '.$log->nama_file);
    }

    public function unduh(LogBackup $backup)
    {
        $path = storage_path('app/backups/'.basename((string) $backup->nama_file));
        abort_unless(is_file($path), 404, 'Berkas backup tidak ditemukan.');

        return response()->download($path, $backup->nama_file);
    }

    public function sql(BackupService $backup)
    {
        $nama = 'simonsa-'.now()->format('Ymd-His').'.sql';

        return response($backup->sql(), 200, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
        ]);
    }

    public function pulihkan(Request $request, BackupService $backup)
    {
        $request->validate([
            'berkas' => ['required', 'file', 'max:51200'],
            'konfirmasi' => ['required', 'in:PULIHKAN'],
        ], Validasi::pesan(), [
            'berkas' => 'berkas backup',
            'konfirmasi' => 'konfirmasi',
        ]);

        $berkas = $request->file('berkas');
        $ext = strtolower($berkas->getClientOriginalExtension());
        $ekstensiValid = \DB::getDriverName() === 'sqlite'
            ? ['sqlite', 'db']
            : ['sql'];
        if (! in_array($ext, $ekstensiValid, true)) {
            $pesan = \DB::getDriverName() === 'sqlite'
                ? 'Pulihkan hanya menerima berkas backup .sqlite.'
                : 'Pulihkan hanya menerima berkas backup .sql.';
            return back()->with('gagal', $pesan);
        }

        $sumber = $berkas->getRealPath();
        if ($user = Auth::user()) {
            \App\Models\AktivitasLogin::catat($user, 'keluar', $user->username, $request->ip());
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        try {
            $backup->pulihkan($sumber);
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('gagal', $e->getMessage());
        }

        return redirect()->route('login')->with('sukses', 'Database berhasil dipulihkan. Silakan masuk kembali.');
    }

    public function destroy(LogBackup $backup, BackupService $layanan)
    {
        $layanan->hapus($backup);

        return back()->with('sukses', 'Berkas backup dihapus.');
    }
}
