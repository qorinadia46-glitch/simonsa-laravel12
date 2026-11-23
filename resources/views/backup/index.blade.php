@extends('layouts.app')

@section('judul', 'Backup Database')
@section('subjudul')
Otomatis setiap hari pukul {{ $jadwal }} WIB
@endsection

@section('aksi')
    <form method="POST" action="{{ route('backup.store') }}">
        @csrf
        <button class="btn-hijau" type="submit">Buat backup sekarang</button>
    </form>
    <a class="btn-putih" href="{{ route('backup.sql') }}">Unduh SQL</a>
@endsection

@section('content')
    <div class="catatan mb-3">Pada komputer lokal, backup harian juga dijalankan saat aplikasi dibuka setelah jadwal. Untuk server, jalankan <code>php artisan schedule:run</code> setiap menit.</div>
    <div class="kartu mb-3">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Waktu</th><th>Berkas</th><th>Ukuran</th><th>Jenis</th><th>Status</th><th>Petugas</th><th></th></tr></thead>
                <tbody>
                @forelse($data as $item)
                    <tr>
                        <td>{{ $item->waktu_backup->translatedFormat('d M Y H:i') }}</td>
                        <td>{{ $item->nama_file }}<div class="small text-secondary">{{ $item->keterangan }}</div></td>
                        <td>{{ $item->ukuranLabel() }}</td>
                        <td>{{ ucfirst($item->jenis_backup) }}</td>
                        <td><span class="lencana {{ $item->status_backup === 'berhasil' ? 'aktif' : 'belum' }}">{{ ucfirst($item->status_backup) }}</span></td>
                        <td>{{ $item->pengguna->nama_user }}</td>
                        <td class="aksi-baris">
                            @if($item->status_backup === 'berhasil')
                                <a class="btn-sm-aksi" href="{{ route('backup.unduh', $item) }}">Unduh</a>
                            @endif
                            <form method="POST" action="{{ route('backup.destroy', $item) }}">
                                @csrf @method('DELETE')
                                <button class="btn-sm-aksi bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong">Belum ada backup. Backup pertama dibuat saat halaman ini atau dashboard dibuka.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $data->links() }}</div>
    </div>

    <form class="kartu kartu-isi" method="POST" action="{{ route('backup.pulihkan') }}" enctype="multipart/form-data">
        @csrf
        <h2 class="h5">Pulihkan database</h2>
        @if(DB::getDriverName() === 'sqlite')
            <p class="text-secondary">Unggah berkas .sqlite hasil backup. Salinan database saat ini disimpan dulu sebelum dipulihkan. Anda akan diminta masuk kembali.</p>
        @else
            <p class="text-secondary">Unggah berkas .sql hasil backup. Salinan database saat ini disimpan dulu sebelum dipulihkan. Anda akan diminta masuk kembali.</p>
        @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Berkas backup</label>
                @if(DB::getDriverName() === 'sqlite')
                    <input class="form-control" type="file" name="berkas" accept=".sqlite,.db" required>
                @else
                    <input class="form-control" type="file" name="berkas" accept=".sql" required>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Ketik PULIHKAN</label>
                <input class="form-control" name="konfirmasi" required>
            </div>
        </div>
        <button class="btn-hitam mt-3" type="submit">Pulihkan</button>
    </form>
@endsection
