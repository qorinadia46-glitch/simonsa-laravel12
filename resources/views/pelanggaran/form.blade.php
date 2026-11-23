@extends('layouts.app')

@section('judul', isset($pelanggaran) ? 'Ubah Pelanggaran' : 'Catat Pelanggaran')
@section('subjudul', 'Poin mengikuti kategori yang dipilih')

@section('content')
    @php
        $ubah = isset($pelanggaran);
        $idSantri = old('id_santri', $pelanggaran->id_santri ?? '');
        $santriTerpilih = $santri->firstWhere('id_santri', $idSantri);
    @endphp
    <form class="kartu kartu-isi" method="POST" action="{{ $ubah ? route('pelanggaran.update', $pelanggaran) : route('pelanggaran.store') }}">
        @csrf
        @if($ubah) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Santri</label>
                <div class="dropdown position-relative" id="pencarianSantri">
                    <input
                        class="form-control"
                        id="cariSantri"
                        type="text"
                        value="{{ $santriTerpilih ? $santriTerpilih->nis.' · '.$santriTerpilih->nama_santri.' · '.$santriTerpilih->kelas : '' }}"
                        placeholder="Ketik nama, NIS, atau kelas santri"
                        autocomplete="off"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="daftarSantri"
                        required
                    >
                    <input type="hidden" name="id_santri" id="idSantri" value="{{ $santriTerpilih?->id_santri ?? '' }}">
                    <div class="dropdown-menu w-100 autocomplete-hasil" id="daftarSantri" role="listbox">
                    @foreach($santri as $item)
                        <button
                            class="dropdown-item"
                            type="button"
                            role="option"
                            data-id="{{ $item->id_santri }}"
                            data-label="{{ $item->nis }} · {{ $item->nama_santri }} · {{ $item->kelas }}"
                            data-search="{{ $item->nis }} {{ $item->nama_santri }} {{ $item->kelas }}"
                        >{{ $item->nis }} · {{ $item->nama_santri }} · {{ $item->kelas }}</button>
                    @endforeach
                        <div class="dropdown-item text-muted" id="santriTidakDitemukan" hidden>Tidak ada santri yang cocok.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Kategori</label>
                <select class="form-select" name="id_kategori" id="kategori" required>
                    <option value="">Pilih kategori</option>
                    @foreach($kategori as $item)
             <option
             value="{{ $item->id_kategori }}"
             data-poin="{{ $item->poin }}"
             @selected(old('id_kategori', $pelanggaran->id_kategori ?? '') == $item->id_kategori)
    >
        {{ $item->kode_pelanggaran }} - {{ $item->nama_pelanggaran }} ({{ $item->poin }} poin)
    </option>
@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input class="form-control" type="date" name="tanggal_pelanggaran" value="{{ old('tanggal_pelanggaran', isset($pelanggaran) ? $pelanggaran->tanggal_pelanggaran->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Poin</label>
                <input class="form-control" id="tampilPoin" value="{{ $pelanggaran->poin_pelanggaran ?? '-' }}" readonly>
            </div>
            <div class="col-12">
                <label class="form-label">Kronologi</label>
                <textarea class="form-control" name="kronologi" rows="4" required>{{ old('kronologi', $pelanggaran->kronologi ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Tindak lanjut</label>
                <textarea class="form-control" name="tindak_lanjut" rows="3">{{ old('tindak_lanjut', $pelanggaran->tindak_lanjut ?? '') }}</textarea>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn-hijau" type="submit">Simpan</button>
            <a class="btn-putih" href="{{ route('pelanggaran.index') }}">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const pencarianSantri = document.getElementById('pencarianSantri');
    const cariSantri = document.getElementById('cariSantri');
    const idSantri = document.getElementById('idSantri');
    const daftarSantri = document.getElementById('daftarSantri');
    const opsiSantri = Array.from(daftarSantri.querySelectorAll('[data-id]'));
    const santriTidakDitemukan = document.getElementById('santriTidakDitemukan');

    const tutupDaftarSantri = () => {
        daftarSantri.classList.remove('show');
        cariSantri.setAttribute('aria-expanded', 'false');
    };

    const tampilkanSantri = () => {
        const kataKunci = cariSantri.value.trim().toLocaleLowerCase('id');
        opsiSantri.forEach((opsi) => {
            opsi.hidden = !opsi.dataset.search.toLocaleLowerCase('id').includes(kataKunci);
        });
        santriTidakDitemukan.hidden = opsiSantri.some((opsi) => !opsi.hidden);
        daftarSantri.classList.add('show');
        cariSantri.setAttribute('aria-expanded', 'true');
    };

    cariSantri.addEventListener('focus', tampilkanSantri);
    cariSantri.addEventListener('input', () => {
        idSantri.value = '';
        cariSantri.setCustomValidity('');
        tampilkanSantri();
    });

    opsiSantri.forEach((opsi) => {
        opsi.addEventListener('click', () => {
            cariSantri.value = opsi.dataset.label;
            idSantri.value = opsi.dataset.id;
            cariSantri.setCustomValidity('');
            tutupDaftarSantri();
        });
    });

    cariSantri.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            tutupDaftarSantri();
        } else if (event.key === 'ArrowDown') {
            const opsiTerlihat = opsiSantri.find((opsi) => !opsi.hidden);
            if (opsiTerlihat) {
                event.preventDefault();
                opsiTerlihat.focus();
            }
        }
    });

    pencarianSantri.addEventListener('focusout', (event) => {
        if (!pencarianSantri.contains(event.relatedTarget)) tutupDaftarSantri();
    });

    pencarianSantri.closest('form').addEventListener('submit', (event) => {
        if (idSantri.value) return;
        event.preventDefault();
        cariSantri.setCustomValidity('Pilih santri dari daftar yang muncul.');
        cariSantri.reportValidity();
    });

    const kategori = document.getElementById('kategori');
    const poin = document.getElementById('tampilPoin');
    const isi = () => {
        const opsi = kategori.selectedOptions[0];
        poin.value = opsi?.dataset.poin || '-';
    };
    kategori.addEventListener('change', isi);
    isi();
</script>
@endpush
