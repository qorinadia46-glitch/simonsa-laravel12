@extends('layouts.app')

@section('judul', 'Kategori Pelanggaran')
@section('subjudul', 'Jenis pelanggaran dan bobot poin')

@section('aksi')
    @if(auth()->user()->admin())
        <a class="btn-hijau" href="{{ route('kategori.import.form') }}">
            <i class="bi bi-file-earmark-excel"></i> Import Excel
        </a>

        <a class="btn-hijau" href="{{ route('kategori.create') }}">
            <i class="bi bi-plus-lg"></i> Tambah Kode Pelanggaran
        </a>
    @endif
@endsection

@section('content')
    <form class="kartu kartu-isi filter mb-3 no-print" method="GET">
        <input class="form-control" name="q" value="{{ $kata }}" placeholder="Cari kode atau nama">
        <select class="form-select" name="status">
            <option value="">Semua status</option>
            @foreach(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $nilai => $label)
                <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn-hijau" type="submit">Cari</button>
    </form>
    <div class="kartu">
        <div class="table-responsive">
            <table class="table tabel-santri">
                <thead>
                    <tr><th class="kolom-no">No</th><th>Kategori</th><th>Poin</th><th>Status</th><th>Deskripsi</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($data as $item)
                    @php
                        $warna = $item->poin >= 25 ? 'sp3' : ($item->poin >= 10 ? 'sp1' : 'aman');
                    @endphp
                    <tr>
                        <td class="kolom-no">{{ $data->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="orang">
                                <span class="inisial kecil">{{ strtoupper(mb_substr($item->kode_pelanggaran, 0, 1)) }}</span>
                                <span>
                                    <strong>{{ $item->nama_pelanggaran }}</strong>
                                    <small>{{ $item->kode_pelanggaran }}</small>
                                </span>
                            </div>
                        </td>
                        <td><b class="poin-angka {{ $warna }}">{{ $item->poin }}</b></td>
                        <td><span class="lencana {{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
                        <td><div class="deskripsi-singkat">{{ $item->deskripsi ?: '-' }}</div></td>
                        <td class="aksi-baris">
                            @if(auth()->user()->admin())
                                <a class="btn-sm-aksi" href="{{ route('kategori.edit', $item) }}">Ubah</a>
                                <form method="POST" action="{{ route('kategori.destroy', $item) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn-sm-aksi bahaya" type="submit">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong">Belum ada kategori.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $data->links() }}</div>
    </div>
@endsection
