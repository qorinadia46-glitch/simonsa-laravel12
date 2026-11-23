@extends('layouts.app')

@section('judul', isset($tazir) ? "Ubah Ta'zir" : "Catat Ta'zir")
@section('subjudul', isset($tazir) ? $tazir->jenis_tazir : 'Status pelaksanaan dapat diperbarui sampai selesai')

@section('content')
    @php
        $ubah = isset($tazir);
        $statusAwal = old('status_pelaksanaan', $tazir->status_pelaksanaan ?? 'menunggu');
        $labelStatus = ['menunggu' => 'Menunggu', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'][$statusAwal] ?? 'Menunggu';
        $idPelanggaran = old('id_pelanggaran', $tazir->id_pelanggaran ?? ($pilihan ?? ''));
        $pelanggaranTerpilih = $pelanggaran->firstWhere('id_pelanggaran', $idPelanggaran);
    @endphp
    <div class="form-layout">
        <form class="kartu kartu-isi formulir" method="POST" action="{{ $ubah ? route('tazir.update', $tazir) : route('tazir.store') }}">
            @csrf
            @if($ubah) @method('PUT') @endif

            <div class="bagian-form">
                <h3>Pelanggaran</h3>
                <label class="form-label" for="cariPelanggaran">Santri dan kategori</label>
                <div class="dropdown position-relative" id="pencarianPelanggaran">
                    <input
                        class="form-control"
                        id="cariPelanggaran"
                        type="text"
                        value="{{ $pelanggaranTerpilih ? $pelanggaranTerpilih->tanggal_pelanggaran->format('d/m/Y').' · '.$pelanggaranTerpilih->santri->nama_santri.' · '.$pelanggaranTerpilih->kategori->nama_pelanggaran : '' }}"
                        placeholder="Ketik nama santri untuk mencari pelanggaran"
                        autocomplete="off"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="daftarPelanggaran"
                        required
                    >
                    <input type="hidden" id="id_pelanggaran" name="id_pelanggaran" value="{{ $pelanggaranTerpilih?->id_pelanggaran ?? '' }}">
                    <div class="dropdown-menu w-100 autocomplete-hasil" id="daftarPelanggaran" role="listbox">
                    @foreach($pelanggaran as $item)
                        <button
                            class="dropdown-item"
                            type="button"
                            role="option"
                            data-id="{{ $item->id_pelanggaran }}"
                            data-label="{{ $item->tanggal_pelanggaran->format('d/m/Y') }} · {{ $item->santri->nama_santri }} · {{ $item->kategori->nama_pelanggaran }}"
                            data-nama="{{ $item->santri->nama_santri }}"
                            data-kategori="{{ $item->kategori->nama_pelanggaran }}"
                            data-search="{{ $item->tanggal_pelanggaran->format('d/m/Y') }} {{ $item->santri->nama_santri }} {{ $item->kategori->nama_pelanggaran }}"
                        >{{ $item->tanggal_pelanggaran->format('d/m/Y') }} · {{ $item->santri->nama_santri }} · {{ $item->kategori->nama_pelanggaran }}</button>
                    @endforeach
                        <div class="dropdown-item text-muted" id="pelanggaranTidakDitemukan" hidden>Tidak ada pelanggaran yang cocok.</div>
                    </div>
                </div>
            </div>

            <div class="bagian-form">
                <h3>Pelaksanaan</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="jenis_tazir">Jenis ta'zir</label>
                        <input class="form-control" id="jenis_tazir" name="jenis_tazir" value="{{ old('jenis_tazir', $tazir->jenis_tazir ?? '') }}" required maxlength="150">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tanggal_pelaksanaan">Tanggal</label>
                        <input class="form-control" id="tanggal_pelaksanaan" type="date" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan', isset($tazir) && $tazir->tanggal_pelaksanaan ? $tazir->tanggal_pelaksanaan->format('Y-m-d') : now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="status_pelaksanaan">Status</label>
                        <select class="form-select" id="status_pelaksanaan" name="status_pelaksanaan">
                            @foreach(['menunggu' => 'Menunggu', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'] as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($statusAwal === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="keterangan">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3">{{ old('keterangan', $tazir->keterangan ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="form-aksi">
                <button class="btn-hijau" type="submit">Simpan</button>
                <a class="btn-putih" href="{{ route('tazir.index') }}">Batal</a>
            </div>
        </form>

        <aside class="kartu pratinjau">
            <div class="inisial" id="pratinjau-inisial">T</div>
            <strong id="pratinjau-nama">{{ old('jenis_tazir', $tazir->jenis_tazir ?? "Jenis ta'zir") }}</strong>
            <div class="chip-bar justify-content-center">
                <span class="chip" id="pratinjau-santri">Santri</span>
                <span class="lencana {{ $statusAwal }}" id="pratinjau-status">{{ $labelStatus }}</span>
            </div>
            <p id="pratinjau-ket">{{ old('keterangan', $tazir->keterangan ?? 'Keterangan pelaksanaan muncul di sini.') }}</p>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
    const pencarianPelanggaran = document.getElementById('pencarianPelanggaran');
    const cariPelanggaran = document.getElementById('cariPelanggaran');
    const idPelanggaran = document.getElementById('id_pelanggaran');
    const daftarPelanggaran = document.getElementById('daftarPelanggaran');
    const opsiPelanggaran = Array.from(daftarPelanggaran.querySelectorAll('[data-id]'));
    const pelanggaranTidakDitemukan = document.getElementById('pelanggaranTidakDitemukan');

    const tutupDaftarPelanggaran = () => {
        daftarPelanggaran.classList.remove('show');
        cariPelanggaran.setAttribute('aria-expanded', 'false');
    };

    const tampilkanPelanggaran = () => {
        const kataKunci = cariPelanggaran.value.trim().toLocaleLowerCase('id');
        opsiPelanggaran.forEach((opsi) => {
            opsi.hidden = !opsi.dataset.search.toLocaleLowerCase('id').includes(kataKunci);
        });
        pelanggaranTidakDitemukan.hidden = opsiPelanggaran.some((opsi) => !opsi.hidden);
        daftarPelanggaran.classList.add('show');
        cariPelanggaran.setAttribute('aria-expanded', 'true');
    };

    cariPelanggaran.addEventListener('focus', tampilkanPelanggaran);
    cariPelanggaran.addEventListener('input', () => {
        idPelanggaran.value = '';
        cariPelanggaran.setCustomValidity('');
        tampilkanPelanggaran();
        segar();
    });

    opsiPelanggaran.forEach((opsi) => {
        opsi.addEventListener('click', () => {
            cariPelanggaran.value = opsi.dataset.label;
            idPelanggaran.value = opsi.dataset.id;
            cariPelanggaran.setCustomValidity('');
            tutupDaftarPelanggaran();
            segar();
        });
    });

    cariPelanggaran.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            tutupDaftarPelanggaran();
        } else if (event.key === 'ArrowDown') {
            const opsiTerlihat = opsiPelanggaran.find((opsi) => !opsi.hidden);
            if (opsiTerlihat) {
                event.preventDefault();
                opsiTerlihat.focus();
            }
        }
    });

    pencarianPelanggaran.addEventListener('focusout', (event) => {
        if (!pencarianPelanggaran.contains(event.relatedTarget)) tutupDaftarPelanggaran();
    });

    pencarianPelanggaran.closest('form').addEventListener('submit', (event) => {
        if (idPelanggaran.value) return;
        event.preventDefault();
        cariPelanggaran.setCustomValidity('Pilih catatan pelanggaran dari daftar yang muncul.');
        cariPelanggaran.reportValidity();
    });

    const isi = (id, teks) => { document.getElementById(id).textContent = teks; };
    const segar = () => {
        const opsi = opsiPelanggaran.find((item) => item.dataset.id === idPelanggaran.value);
        const nama = opsi?.dataset.nama || '';
        const jenis = document.getElementById('jenis_tazir').value.trim();
        const ket = document.getElementById('keterangan').value.trim();
        const status = document.getElementById('status_pelaksanaan');
        isi('pratinjau-nama', jenis || "Jenis ta'zir");
        isi('pratinjau-inisial', (nama || jenis || 'T').charAt(0).toUpperCase());
        isi('pratinjau-santri', nama ? nama + ' · ' + (opsi.dataset.kategori || '') : 'Pilih pelanggaran');
        isi('pratinjau-status', status.selectedOptions[0].textContent);
        document.getElementById('pratinjau-status').className = 'lencana ' + status.value;
        isi('pratinjau-ket', ket || 'Keterangan pelaksanaan muncul di sini.');
    };
    ['jenis_tazir', 'status_pelaksanaan', 'keterangan'].forEach((id) => {
        document.getElementById(id).addEventListener('input', segar);
        document.getElementById(id).addEventListener('change', segar);
    });
    segar();
</script>
@endpush
