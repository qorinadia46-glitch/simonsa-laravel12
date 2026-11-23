@extends('layouts.app')

@section('judul', 'Data Santri')
@section('subjudul', 'Daftar santri dan akumulasi poin')

@section('aksi')
    @if(auth()->user()->admin())
        <a class="btn-hijau" href="{{ route('santri.import.form') }}">
            <i class="bi bi-file-earmark-excel"></i> Import Excel
        </a>

        <a class="btn-hijau" href="{{ route('santri.create') }}">
            <i class="bi bi-plus-lg"></i> Tambah santri
        </a>
    @endif
@endsection

@section('content')
    <form class="kartu kartu-isi filter mb-3 no-print" method="GET">
        <input class="form-control" name="q" value="{{ $kata }}" placeholder="Cari nama, NIS, kamar">
        <select class="form-select" name="kelas">
            <option value="">Semua kelas</option>
            @foreach($kelas as $item)
                <option value="{{ $item }}" @selected(request('kelas') === $item)>{{ $item }}</option>
            @endforeach
        </select>
        <select class="form-select" name="status">
            <option value="">Semua status</option>
            @foreach(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'alumni' => 'Alumni'] as $nilai => $label)
                <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn-hijau" type="submit">Cari</button>
    </form>

    <div class="kartu">
        <div class="table-responsive">
            <table class="table tabel-santri">
                <thead>
                    <tr><th class="kolom-no">No</th><th>Santri</th><th>Kelas</th><th>Kamar</th><th>Poin</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($data as $santri)
                    @php
                        $tingkat = $santri->tingkatSaatIni();
                        $warna = $tingkat ? strtolower($tingkat) : 'aman';
                    @endphp
                    <tr>
                        <td class="kolom-no">{{ $data->firstItem() + $loop->index }}</td>
                        <td>
                            <a class="orang" href="{{ route('santri.show', $santri) }}">
                                <span class="inisial kecil">{{ strtoupper(mb_substr($santri->nama_santri, 0, 1)) }}</span>
                                <span>
                                    <strong>{{ $santri->nama_santri }}</strong>
                                    <small>NIS {{ $santri->nis }}</small>
                                </span>
                            </a>
                        </td>
                        <td>{{ $santri->kelas ?: '-' }}</td>
                        <td>{{ $santri->kamar ?: '-' }}</td>
                        <td>
                            <div class="poin-sel">
                                <b class="poin-angka {{ $warna }}">{{ $santri->poinTerkumpul() }}</b>
                                @include('partials.tingkat', ['tingkat' => $tingkat])
                            </div>
                        </td>
                        <td><span class="lencana {{ $santri->status }}">{{ ucfirst($santri->status) }}</span></td>
                        <td class="aksi-baris">
                            <a class="btn-sm-aksi" href="{{ route('santri.show', $santri) }}">Riwayat</a>
                            @if(auth()->user()->admin())
                                <a class="btn-sm-aksi" href="{{ route('santri.edit', $santri) }}">Ubah</a>
                                <form method="POST" action="{{ route('santri.destroy', $santri) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn-sm-aksi bahaya" type="submit">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong">Belum ada data santri.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $data->links() }}</div>
    </div>
@endsection
