@extends('layouts.app')

@section('judul', isset($santri) ? 'Ubah Santri' : 'Tambah Santri')
@section('subjudul', isset($santri) ? $santri->nama_santri : 'Isi data induk santri')

@section('content')
    @php $ubah = isset($santri); @endphp
    <div class="form-layout">
        <form class="kartu kartu-isi formulir" method="POST" action="{{ $ubah ? route('santri.update', $santri) : route('santri.store') }}">
            @csrf
            @if($ubah) @method('PUT') @endif

            <div class="bagian-form">
                <h3>Identitas</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="nis">NIS</label>
                        <input
    type="text"
    class="form-control"
    id="nis"
    name="nis"
    value="{{ old('nis', $santri->nis ?? '') }}"
    inputmode="numeric"
    pattern="[0-9]+"
    maxlength="20"
    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
    required
>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="nama_santri">Nama santri</label>
                        <input class="form-control" id="nama_santri" name="nama_santri" value="{{ old('nama_santri', $santri->nama_santri ?? '') }}" required maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="jenis_kelamin">Jenis kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                            @foreach(['Laki-laki', 'Perempuan'] as $jk)
                                <option value="{{ $jk }}" @selected(old('jenis_kelamin', $santri->jenis_kelamin ?? 'Laki-laki') === $jk)>{{ $jk }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            @foreach(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'alumni' => 'Alumni'] as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('status', $santri->status ?? 'aktif') === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="bagian-form">
                <h3>Penempatan</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="kelas">Kelas</label>
                        <input class="form-control" id="kelas" name="kelas" value="{{ old('kelas', $santri->kelas ?? '') }}" maxlength="30" placeholder="10 MA">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="kamar">Kamar</label>
                        <input class="form-control" id="kamar" name="kamar" value="{{ old('kamar', $santri->kamar ?? '') }}" maxlength="50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="tanggal_masuk">Tanggal masuk</label>
                        <input class="form-control" id="tanggal_masuk" type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', isset($santri) && $santri->tanggal_masuk ? $santri->tanggal_masuk->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="alamat">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3">{{ old('alamat', $santri->alamat ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="form-aksi">
                <button class="btn-hijau" type="submit">Simpan</button>
                <a class="btn-putih" href="{{ $ubah ? route('santri.show', $santri) : route('santri.index') }}">Batal</a>
            </div>
        </form>

        <aside class="kartu pratinjau">
            <div class="inisial" id="pratinjau-inisial">{{ $ubah ? strtoupper(mb_substr($santri->nama_santri, 0, 1)) : 'S' }}</div>
            <strong id="pratinjau-nama">{{ $ubah ? $santri->nama_santri : 'Nama santri' }}</strong>
            <div class="chip-bar justify-content-center" id="pratinjau-chip">
                <span class="chip" id="pratinjau-nis">NIS {{ $ubah ? $santri->nis : '-' }}</span>
                <span class="chip" id="pratinjau-kelas">{{ $ubah && $santri->kelas ? $santri->kelas : 'Kelas' }}</span>
                <span class="chip" id="pratinjau-kamar">Kamar {{ $ubah && $santri->kamar ? $santri->kamar : '-' }}</span>
            </div>
            @if($ubah)
                <a class="btn-putih" href="{{ route('santri.show', $santri) }}">Lihat detail</a>
            @else
                <p>Pratinjau ikut berubah saat data diisi.</p>
            @endif
        </aside>
    </div>
@endsection

@push('scripts')
<script>
    const isi = (id, teks) => { document.getElementById(id).textContent = teks; };
    const nama = document.getElementById('nama_santri');
    const segar = () => {
        const n = nama.value.trim();
        isi('pratinjau-nama', n || 'Nama santri');
        isi('pratinjau-inisial', (n || 'S').charAt(0).toUpperCase());
        isi('pratinjau-nis', 'NIS ' + (document.getElementById('nis').value.trim() || '-'));
        isi('pratinjau-kelas', document.getElementById('kelas').value.trim() || 'Kelas');
        isi('pratinjau-kamar', 'Kamar ' + (document.getElementById('kamar').value.trim() || '-'));
    };
    ['nama_santri', 'nis', 'kelas', 'kamar'].forEach((id) => {
        document.getElementById(id).addEventListener('input', segar);
    });
</script>
@endpush
