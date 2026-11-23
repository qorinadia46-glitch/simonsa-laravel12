@extends('layouts.app')

@section('judul', 'Detail Pelanggaran')
@section('subjudul', $pelanggaran->kategori->nama_pelanggaran)

@section('aksi')
    <a class="btn-putih" href="{{ route('pelanggaran.index') }}">Kembali</a>
    @if(auth()->user()->admin())
        <a class="btn-putih" href="{{ route('pelanggaran.edit', $pelanggaran) }}">Ubah</a>
    @endif
    @if(auth()->user()->petugas())
        <a class="btn-hijau" href="{{ route('tazir.create', ['pelanggaran' => $pelanggaran->id_pelanggaran]) }}">Catat ta'zir</a>
    @endif
@endsection

@section('content')
    @php
        $santri = $pelanggaran->santri;
        $progres = \App\Services\PoinService::progres($santri->poinTerkumpul());
    @endphp

    <section class="profil">
        <div class="kartu profil-utama">
            <div class="profil-kepala">
                <div class="inisial">{{ strtoupper(mb_substr($santri->nama_santri, 0, 1)) }}</div>
                <div>
                    <h2><a href="{{ route('santri.show', $santri) }}">{{ $santri->nama_santri }}</a></h2>
                    <div class="chip-bar">
                        <span class="chip">NIS {{ $santri->nis }}</span>
                        <span class="chip">{{ $santri->kelas ?: 'Kelas belum diisi' }}</span>
                        <span class="chip">Kamar {{ $santri->kamar ?: '-' }}</span>
                        <span class="chip">{{ $pelanggaran->tanggal_pelanggaran->translatedFormat('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="kartu profil-poin">
            <div class="d-flex justify-content-between align-items-center">
                <span>Poin pelanggaran ini</span>
                @include('partials.tingkat', ['tingkat' => $santri->tingkatSaatIni()])
            </div>
            <div class="angka">{{ $pelanggaran->poin_pelanggaran }}</div>
            <div class="bar"><span style="width: {{ $progres['persen'] }}%"></span></div>
            <small>Akumulasi {{ $santri->poinTerkumpul() }} poin · {{ $progres['label'] }}</small>
        </div>
    </section>

    <section class="info-grid">
        <div class="kartu info-item">
            <span>Kategori</span>
            <strong>{{ $pelanggaran->kategori->nama_pelanggaran }}</strong>
        </div>
        <div class="kartu info-item">
            <span>Dicatat oleh</span>
            <strong>{{ $pelanggaran->pengguna->nama_user }}</strong>
        </div>
        <div class="kartu info-item">
            <span>Waktu catat</span>
            <strong>{{ $pelanggaran->created_at?->translatedFormat('d F Y H:i') ?: '-' }}</strong>
        </div>
    </section>

    <div class="kartu mb-3">
        <div class="judul-bagian"><strong><i class="bi bi-card-text"></i> Kronologi</strong></div>
        <div class="kartu-isi"><p class="mb-0">{{ $pelanggaran->kronologi }}</p></div>
    </div>

    <div class="kartu mb-3">
        <div class="judul-bagian"><strong><i class="bi bi-signpost"></i> Tindak lanjut</strong></div>
        <div class="kartu-isi"><p class="mb-0">{{ $pelanggaran->tindak_lanjut ?: 'Belum ada tindak lanjut.' }}</p></div>
    </div>

    <div class="kartu">
        <div class="judul-bagian">
            <strong><i class="bi bi-exclamation-diamond"></i> Ta'zir</strong>
            <span class="text-secondary">{{ $pelanggaran->tazir->count() }} catatan</span>
        </div>
        <div class="kartu-isi daftar-riwayat">
            @forelse($pelanggaran->tazir as $item)
                <article class="riwayat-item">
                    <div class="riwayat-no">{{ $loop->iteration }}</div>
                    <div>
                        <div class="riwayat-atas">
                            <strong>{{ $item->jenis_tazir }}</strong>
                            <span class="lencana {{ $item->status_pelaksanaan }}">{{ $item->labelStatus() }}</span>
                        </div>
                        @if($item->keterangan)<p>{{ $item->keterangan }}</p>@endif
                        <small>{{ $item->tanggal_pelaksanaan?->translatedFormat('d M Y') ?: 'Tanggal belum diisi' }} · {{ $item->pengguna->nama_user }}</small>
                    </div>
                </article>
            @empty
                <div class="kosong">Belum ada ta'zir untuk pelanggaran ini.</div>
            @endforelse
        </div>
    </div>
@endsection
