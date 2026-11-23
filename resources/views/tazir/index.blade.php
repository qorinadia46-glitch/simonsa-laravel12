@extends('layouts.app')

@section('judul', "Pelaksanaan Ta'zir")
@section('subjudul', 'Catatan pembinaan setelah pelanggaran')

@section('aksi')
    <a class="btn-hijau" href="{{ route('tazir.create') }}"><i class="bi bi-plus-lg"></i> Catat ta'zir</a>
@endsection

@section('content')
    <form class="kartu kartu-isi filter mb-3" method="GET">
        <input class="form-control" name="q" value="{{ $kata }}" placeholder="Jenis atau nama santri">
        <select class="form-select" name="status">
            <option value="">Semua status</option>
            @foreach(['menunggu' => 'Menunggu', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'] as $nilai => $label)
                <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn-hijau" type="submit">Cari</button>
    </form>
    <div class="kartu">
        <div class="table-responsive">
            <table class="table tabel-santri">
                <thead><tr><th class="kolom-no">No</th><th>Santri</th><th>Pelanggaran</th><th>Ta'zir</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($data as $item)
                    <tr>
                        <td class="kolom-no">{{ $data->firstItem() + $loop->index }}</td>
                        <td>
                            <a class="orang" href="{{ route('santri.show', $item->pelanggaran->santri) }}">
                                <span class="inisial kecil">{{ strtoupper(mb_substr($item->pelanggaran->santri->nama_santri, 0, 1)) }}</span>
                                <span>
                                    <strong>{{ $item->pelanggaran->santri->nama_santri }}</strong>
                                    <small>{{ $item->pelanggaran->santri->nis }} · {{ $item->pelanggaran->santri->kelas }}</small>
                                </span>
                            </a>
                        </td>
                        <td>{{ $item->pelanggaran->kategori->nama_pelanggaran }}</td>
                        <td>
                            <strong>{{ $item->jenis_tazir }}</strong>
                            @if($item->keterangan)<div class="deskripsi-singkat">{{ $item->keterangan }}</div>@endif
                        </td>
                        <td>{{ $item->tanggal_pelaksanaan?->translatedFormat('d M Y') ?: '-' }}</td>
                        <td><span class="lencana {{ $item->status_pelaksanaan }}">{{ $item->labelStatus() }}</span></td>
                        <td class="aksi-baris">
                            @if(auth()->user()->admin())
                                <a class="btn-sm-aksi" href="{{ route('tazir.edit', $item) }}">Ubah</a>
                                <form method="POST" action="{{ route('tazir.destroy', $item) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn-sm-aksi bahaya" type="submit">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong">Belum ada data ta'zir.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $data->links() }}</div>
    </div>
@endsection
