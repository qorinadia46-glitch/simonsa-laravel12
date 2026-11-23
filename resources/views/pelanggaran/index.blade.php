@extends('layouts.app')

@section('judul', 'Data Pelanggaran')
@section('subjudul', 'Catatan pelanggaran yang masuk ke akumulasi poin')

@section('aksi')
    @if(auth()->user()->petugas())
        <a class="btn-hijau" href="{{ route('pelanggaran.create') }}"><i class="bi bi-plus-lg"></i> Catat pelanggaran</a>
    @endif
@endsection

@section('content')
    <form class="kartu kartu-isi filter mb-3 no-print" method="GET">
    <input
        class="form-control"
        name="q"
        value="{{ $filter['q'] ?? '' }}"
        placeholder="Cari nama, atau NIS."
    >

    <button class="btn-hijau" type="submit">
        <i class="bi bi-search"></i> Cari
    </button>
    </form>
    <div class="kartu">
        <div class="table-responsive">
            <table class="table tabel-santri">
                <thead><tr><th class="kolom-no">No</th><th>Santri</th><th>Daftar pelanggaran</th></tr></thead>
                <tbody>
                @forelse($data as $item)
                    <tr>
                        <td class="kolom-no">{{ $data->firstItem() + $loop->index }}</td>
                        <td>
                            <a class="orang" href="{{ route('santri.show', $item) }}">
                                <span class="inisial kecil">{{ strtoupper(mb_substr($item->nama_santri, 0, 1)) }}</span>
                                <span>
                                    <strong>{{ $item->nama_santri }}</strong>
                                    <small>{{ $item->nis }} · {{ $item->kelas }}</small>
                                </span>
                            </a>
                        </td>
                        <td class="daftar-pelanggaran-cell">
                            <table class="daftar-pelanggaran">
                                <thead>
                                    <tr><th>Tanggal</th><th>Kategori</th><th>Poin</th><th>Petugas</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                @foreach($item->pelanggaran as $pelanggaran)
                                    @php $warna = $pelanggaran->poin_pelanggaran >= 25 ? 'sp3' : ($pelanggaran->poin_pelanggaran >= 10 ? 'sp1' : 'aman'); @endphp
                                    <tr>
                                        <td>{{ $pelanggaran->tanggal_pelanggaran->translatedFormat('d M Y') }}</td>
                                        <td>{{ $pelanggaran->kategori->nama_pelanggaran }}</td>
                                        <td><b class="poin-angka {{ $warna }}">{{ $pelanggaran->poin_pelanggaran }}</b></td>
                                        <td>{{ $pelanggaran->pengguna?->nama_user ?? '-' }}</td>
                                        <td class="aksi-baris">
                                            <a class="btn-sm-aksi" href="{{ route('pelanggaran.show', $pelanggaran) }}">Detail</a>
                                            @if(auth()->user()->admin())
                                                <a class="btn-sm-aksi" href="{{ route('pelanggaran.edit', $pelanggaran) }}">Ubah</a>
                                            @endif
                                            @if(auth()->user()->admin())
                                                <form method="POST" action="{{ route('pelanggaran.destroy', $pelanggaran) }}">
                                                    @csrf @method('DELETE')
                                                    <button class="btn-sm-aksi bahaya" type="submit">Hapus</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="kosong">Belum ada pelanggaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $data->links() }}</div>
    </div>
@endsection
