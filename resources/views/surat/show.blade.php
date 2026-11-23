@extends('layouts.app')

@section('judul', 'Detail Surat')
@section('subjudul', $surat->nomor_surat)

@section('aksi')
    <a class="btn-putih" href="{{ route('surat.index') }}">Kembali</a>
    <a class="btn-putih" href="{{ route('surat.edit', $surat) }}">Ubah</a>
    <a class="btn-hijau" href="{{ route('surat.cetak', $surat) }}" target="_blank">Cetak</a>
@endsection

@section('content')
    @php
        $santri = $surat->santri;
        $warna = strtolower($surat->tingkat_peringatan);
        $paragraf = preg_split("/\n\s*\n/", trim($surat->isi_surat)) ?: [];
        $daftar = $santri->pelanggaran
            ->filter(fn ($item) => $item->tanggal_pelanggaran->lte($surat->tanggal_surat))
            ->sortBy('tanggal_pelanggaran')
            ->values();
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
                        @include('partials.tingkat', ['tingkat' => $surat->tingkat_peringatan])
                    </div>
                </div>
            </div>
        </div>
        <div class="kartu profil-poin">
            <span>Total poin surat</span>
            <div class="angka">{{ $surat->total_poin }}</div>
            <small>{{ $surat->tanggal_surat->translatedFormat('d F Y') }}</small>
        </div>
    </section>

    <section class="info-grid">
        <div class="kartu info-item">
            <span>Nomor surat</span>
            <strong>{{ $surat->nomor_surat }}</strong>
        </div>
        <div class="kartu info-item">
            <span>Diterbitkan oleh</span>
            <strong>{{ $surat->pengguna->nama_user }}</strong>
        </div>
        <div class="kartu info-item">
            <span>Waktu catat</span>
            <strong>{{ $surat->created_at?->translatedFormat('d F Y H:i') ?: '-' }}</strong>
        </div>
    </section>

    <div class="kartu mb-3">
        <div class="judul-bagian"><strong><i class="bi bi-envelope-paper"></i> Isi surat</strong></div>
        <div class="kartu-isi isi-surat-layar">
            @foreach($paragraf as $teks)
                <p>{{ $teks }}</p>
            @endforeach
        </div>
    </div>

    <div class="kartu">
        <div class="judul-bagian">
            <strong><i class="bi bi-journal-x"></i> Pelanggaran sampai tanggal surat</strong>
            <span class="text-secondary">{{ $daftar->count() }} catatan</span>
        </div>
        <div class="kartu-isi daftar-riwayat">
            @forelse($daftar as $item)
                <article class="riwayat-item">
                    <div class="riwayat-no">{{ $loop->iteration }}</div>
                    <div>
                        <div class="riwayat-atas">
                            <a href="{{ route('pelanggaran.show', $item) }}">{{ $item->kategori->nama_pelanggaran }}</a>
                            <b class="poin-angka {{ $warna }}">{{ $item->poin_pelanggaran }}</b>
                        </div>
                        <small>{{ $item->tanggal_pelanggaran->translatedFormat('d M Y') }}</small>
                    </div>
                </article>
            @empty
                <div class="kosong">Belum ada catatan pelanggaran sampai tanggal surat.</div>
            @endforelse
        </div>
    </div>
@endsection
