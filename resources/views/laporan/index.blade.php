@extends('layouts.app')

@section('judul', 'Laporan dan Grafik')
@section('subjudul', 'Rekap pelanggaran sesuai saringan')

@section('aksi')
    @if(auth()->user()->admin())
        <div class="dropdown">
            <button class="btn-hijau dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Unduh Data
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($pilihanLaporan as $jenis => $label)
                    <li>
                        <a class="dropdown-item" href="{{ route('laporan.index', array_merge($filter, ['jenis' => $jenis])) }}">
                            <i class="bi bi-file-earmark-excel text-success me-2"></i>{{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <button class="btn-hijau" id="cetakLaporan" type="button" data-url="{{ route('laporan.cetak') }}" data-token="{{ csrf_token() }}">Cetak laporan</button>
    @endif
@endsection

@section('content')
    <header class="kop-cetak">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo pondok">
        @endif
        <div>
            <div class="merek">SIMONSA</div>
            <strong>{{ $namaPondok }}</strong>
            <p>Sistem Monitoring Pelanggaran Santri</p>
        </div>
        <div class="judul-cetak">
            <b>{{ $jenisLaporan ? strtoupper($pilihanLaporan[$jenisLaporan]) : 'LAPORAN PELANGGARAN SANTRI' }}</b>
            <span>Dicetak {{ now()->translatedFormat('d F Y H:i') }} WIB</span>
        </div>
    </header>

    <form class="kartu kartu-isi filter mb-3 no-print" method="GET">
        @if($jenisLaporan)
            <input type="hidden" name="jenis" value="{{ $jenisLaporan }}">
        @endif
        <input class="form-control" name="q" value="{{ $filter['q'] ?? '' }}" placeholder="Nama atau NIS">
        <select class="form-select" name="id_kategori">
            <option value="">Semua kategori</option>
            @foreach($kategori as $item)
                <option value="{{ $item->id_kategori }}" @selected(($filter['id_kategori'] ?? '') == $item->id_kategori)>{{ $item->nama_pelanggaran }}</option>
            @endforeach
        </select>
        <select class="form-select" name="kelas">
            <option value="">Semua kelas</option>
            @foreach($kelas as $item)
                <option value="{{ $item }}" @selected(($filter['kelas'] ?? '') === $item)>{{ $item }}</option>
            @endforeach
        </select>
        <input class="form-control" type="date" name="dari" value="{{ $filter['dari'] ?? '' }}">
        <input class="form-control" type="date" name="sampai" value="{{ $filter['sampai'] ?? '' }}">
        <button class="btn-hijau" type="submit">Terapkan</button>
    </form>

    @if($jenisLaporan)
        <section class="kartu kartu-aksen" id="pratinjau">
            <div class="kartu-isi">
                <h2 class="h5 mb-1">{{ $pilihanLaporan[$jenisLaporan] }}</h2>
                <p class="text-muted mb-3">{{ $pratinjau->total() }} santri ditemukan. Pilih santri untuk menentukan isi unduhan.</p>

                @error('santri_ids')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('laporan.unduh-pilihan') }}" id="formUnduhPilihan">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenisLaporan }}">
                    @foreach($filter as $namaFilter => $nilaiFilter)
                        <input type="hidden" name="{{ $namaFilter }}" value="{{ $nilaiFilter }}">
                    @endforeach

                    <div class="d-flex align-items-center flex-wrap gap-3 mb-3 no-print">
                        <label class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="semua_santri" value="1" id="pilihSemuaHasil">
                            <span class="form-check-label">Pilih semua hasil</span>
                        </label>
                        <label class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="pilihHalaman">
                            <span class="form-check-label">Pilih halaman ini</span>
                        </label>
                        <span class="text-muted" id="jumlahDipilih">0 santri dipilih</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table tabel-santri laporan-santri">
                            @if($jenisLaporan === 'data-santri')
                                <thead><tr><th class="no-print">Pilih</th><th>NIS</th><th>Nama Santri</th><th>Jenis Kelamin</th><th>Kelas</th><th>Kamar</th><th>Status</th></tr></thead>
                                <tbody>
                                @forelse($pratinjau as $item)
                                    <tr>
                                        <td class="no-print"><input class="form-check-input pilih-santri" type="checkbox" name="santri_ids[]" value="{{ $item->id_santri }}" aria-label="Pilih {{ $item->nama_santri }}"></td>
                                        <td>{{ $item->nis }}</td>
                                        <td>{{ $item->nama_santri }}</td>
                                        <td>{{ $item->jenis_kelamin }}</td>
                                        <td>{{ $item->kelas }}</td>
                                        <td>{{ $item->kamar }}</td>
                                        <td>{{ $item->status }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="kosong">Tidak ada santri pada pencarian ini.</td></tr>
                                @endforelse
                                </tbody>
                            @elseif($jenisLaporan === 'akumulasi-poin')
                                <thead><tr><th class="no-print">Pilih</th><th>NIS</th><th>Nama Santri</th><th>Kelas</th><th>Jumlah Pelanggaran</th><th>Akumulasi Poin</th></tr></thead>
                                <tbody>
                                @forelse($pratinjau as $item)
                                    <tr>
                                        <td class="no-print"><input class="form-check-input pilih-santri" type="checkbox" name="santri_ids[]" value="{{ $item->id_santri }}" aria-label="Pilih {{ $item->nama_santri }}"></td>
                                        <td>{{ $item->nis }}</td>
                                        <td>{{ $item->nama_santri }}</td>
                                        <td>{{ $item->kelas }}</td>
                                        <td>{{ $item->pelanggaran_count }}</td>
                                        <td>{{ (int) $item->total_poin }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="kosong">Tidak ada santri pada pencarian ini.</td></tr>
                                @endforelse
                                </tbody>
                            @elseif($jenisLaporan === 'surat-peringatan')
                                <thead><tr><th class="no-print">Pilih</th><th>NIS</th><th>Nama Santri</th><th>Jumlah Surat</th><th>Surat Terakhir</th><th>Tanggal</th><th>Total Poin</th></tr></thead>
                                <tbody>
                                @forelse($pratinjau as $item)
                                    @php
                                        $suratTerakhir = $item->suratPeringatan->first();
                                    @endphp
                                    <tr>
                                        <td class="no-print"><input class="form-check-input pilih-santri" type="checkbox" name="santri_ids[]" value="{{ $item->id_santri }}" aria-label="Pilih {{ $item->nama_santri }}"></td>
                                        <td>{{ $item->nis }}</td>
                                        <td>{{ $item->nama_santri }}</td>
                                        <td>{{ $item->surat_peringatan_count }}</td>
                                        <td>{{ $suratTerakhir?->tingkat_peringatan ?? '-' }}</td>
                                        <td>{{ $suratTerakhir?->tanggal_surat?->format('d-m-Y') ?? '-' }}</td>
                                        <td>{{ $suratTerakhir?->total_poin ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="kosong">Tidak ada surat peringatan pada pencarian ini.</td></tr>
                                @endforelse
                                </tbody>
                            @else
                                <thead><tr><th class="no-print">Pilih</th><th>NIS</th><th>Nama Santri</th><th>Kelas</th><th>Jumlah Pelanggaran</th><th>Total Poin</th><th>Rincian Pelanggaran</th></tr></thead>
                                <tbody>
                                @forelse($pratinjau as $item)
                                    <tr>
                                        <td class="no-print"><input class="form-check-input pilih-santri" type="checkbox" name="santri_ids[]" value="{{ $item->id_santri }}" aria-label="Pilih {{ $item->nama_santri }}"></td>
                                        <td>{{ $item->nis }}</td>
                                        <td>{{ $item->nama_santri }}</td>
                                        <td>{{ $item->kelas }}</td>
                                        <td>{{ $item->jumlah_pelanggaran }}</td>
                                        <td>{{ (int) $item->total_poin }}</td>
                                        <td>
                                            <details>
                                                <summary>Lihat rincian</summary>
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm">
                                                        <thead><tr><th>Tanggal</th><th>Jenis Pelanggaran</th><th>Poin</th><th>Keterangan</th></tr></thead>
                                                        <tbody>
                                                        @foreach($item->pelanggaran as $pelanggaran)
                                                            <tr>
                                                                <td>{{ $pelanggaran->tanggal_pelanggaran->format('d-m-Y') }}</td>
                                                                <td>{{ $pelanggaran->kategori->nama_pelanggaran }}</td>
                                                                <td>{{ $pelanggaran->poin_pelanggaran }}</td>
                                                                <td>{{ $pelanggaran->kronologi }}</td>
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="kosong">Tidak ada santri pada pencarian ini.</td></tr>
                                @endforelse
                                </tbody>
                            @endif
                        </table>
                    </div>

                    <div class="pager no-print">{{ $pratinjau->links() }}</div>
                    <div class="alert alert-warning mt-3 d-none no-print" id="pesanPilihSantri">
                        Pilih setidaknya satu santri atau centang semua hasil sebelum mengunduh.
                    </div>
                    <div class="d-flex justify-content-end mt-3 no-print">
                        <button class="btn-hijau" type="submit">
                            <i class="bi bi-download"></i> Unduh Laporan
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @else
    <div class="stat-grid mb-3">
        <article class="stat hijau">
            <div><span>Kasus</span><b>{{ $ringkas['jumlah'] }}</b></div>
            <div class="stat-ikon"><i class="bi bi-journal-x"></i></div>
        </article>
        <article class="stat kuning">
            <div><span>Total poin</span><b>{{ $ringkas['poin'] }}</b></div>
            <div class="stat-ikon"><i class="bi bi-123"></i></div>
        </article>
        <article class="stat hitam">
            <div><span>Santri terlibat</span><b>{{ $ringkas['santri'] }}</b></div>
            <div class="stat-ikon"><i class="bi bi-people"></i></div>
        </article>
        <article class="stat merah">
            <div><span>Batas SP</span><b style="font-size:18px">{{ $batas['sp1'] }} / {{ $batas['sp2'] }} / {{ $batas['sp3'] }}</b></div>
            <div class="stat-ikon"><i class="bi bi-flag"></i></div>
        </article>
    </div>

    <div class="row g-3 mb-3" id="grafik">
        <div class="col-lg-7 grafik-kolom">
            <div class="kartu kartu-isi kartu-aksen hijau">
                <strong>Jumlah pelanggaran</strong>
                <div class="chart-box mt-3"><canvas id="grafikBulan"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5 grafik-kolom">
            <div class="kartu kartu-isi kartu-aksen kuning">
                <strong>Per kategori</strong>
                <div class="chart-box donat mt-3"><canvas id="grafikKategori"></canvas></div>
            </div>
        </div>
    </div>

    <div class="kartu kartu-aksen" id="tabel">
        <div class="table-responsive">
            <table class="table tabel-santri laporan-santri">
                <thead><tr><th class="kolom-no">No</th><th>Santri</th><th>Jumlah Pelanggaran</th><th>Total Poin</th><th>Tanggal Terakhir</th><th>Petugas Terakhir</th></tr></thead>
                <tbody>
                @forelse($data as $item)
                    @php
                        $jumlahPelanggaran = $item->pelanggaran->count();
                        $totalPoin = $item->pelanggaran->sum('poin_pelanggaran');
                        $pelanggaranTerakhir = $item->pelanggaran->first();
                        $kategoriRingkas = $item->pelanggaran->pluck('kategori.nama_pelanggaran')->filter()->unique()->values();
                        $warna = $totalPoin >= 25 ? 'sp3' : ($totalPoin >= 10 ? 'sp1' : 'aman');
                    @endphp
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
                        <td>
                            <strong>{{ $jumlahPelanggaran }} pelanggaran</strong>
                            <small class="kategori-ringkas">{{ $kategoriRingkas->join(' · ') }}</small>
                        </td>
                        <td><b class="poin-angka {{ $warna }}">{{ $totalPoin }}</b></td>
                        <td>{{ $pelanggaranTerakhir?->tanggal_pelanggaran->translatedFormat('d M Y') ?? '-' }}</td>
                        <td>{{ $pelanggaranTerakhir?->pengguna?->nama_user ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong">Tidak ada data pada saringan ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager no-print">{{ $data->links() }}</div>
    </div>
    @endif
@endsection

@push('scripts')
@if(!$jenisLaporan)
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
    const warna = ['#1b8f4a', '#e0a11a', '#c2410c', '#b42318', '#14657a', '#4c3bb5', '#1aa58a', '#d4537e', '#3d5a80', '#8a6a2f'];
    const bulan = @json($chart['nilaiBulan']);
    new Chart(document.getElementById('grafikBulan'), {
        type: 'bar',
        data: { labels: @json($chart['labelBulan']), datasets: [{ data: bulan, backgroundColor: bulan.map((_, i) => warna[i % warna.length]), borderRadius: 8 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    new Chart(document.getElementById('grafikKategori'), {
        type: 'doughnut',
        data: { labels: @json($chart['labelKategori']), datasets: [{ data: @json($chart['nilaiKategori']), backgroundColor: warna }] },
        options: {
            maintainAspectRatio: false,
            cutout: '58%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } }
            }
        }
    });
</script>
@endif
<script>
    document.getElementById('cetakLaporan')?.addEventListener('click', async (event) => {
        const tombol = event.currentTarget;
        await fetch(tombol.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tombol.dataset.token, 'Accept': 'application/json' }
        });
        window.print();
    });

    const formUnduhPilihan = document.getElementById('formUnduhPilihan');
    if (formUnduhPilihan) {
        const checkboxSemua = document.getElementById('pilihSemuaHasil');
        const checkboxHalaman = document.getElementById('pilihHalaman');
        const checkboxSantri = Array.from(formUnduhPilihan.querySelectorAll('.pilih-santri'));
        const jumlahDipilih = document.getElementById('jumlahDipilih');
        const pesanPilihSantri = document.getElementById('pesanPilihSantri');
        const parameter = new URLSearchParams(window.location.search);
        parameter.delete('page');
        const kunciPilihan = `simonsa:laporan:${parameter.toString()}`;
        const pilihanTersimpan = new Set(JSON.parse(sessionStorage.getItem(kunciPilihan) || '[]'));

        const perbaruiPilihan = () => {
            sessionStorage.setItem(kunciPilihan, JSON.stringify(Array.from(pilihanTersimpan)));
            checkboxSantri.forEach((checkbox) => {
                checkbox.checked = pilihanTersimpan.has(checkbox.value);
            });
            checkboxHalaman.checked = checkboxSantri.length > 0
                && checkboxSantri.every((checkbox) => checkbox.checked);
            jumlahDipilih.textContent = checkboxSemua.checked
                ? 'Semua hasil dipilih'
                : `${pilihanTersimpan.size} santri dipilih`;
        };

        checkboxSantri.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    pilihanTersimpan.add(checkbox.value);
                } else {
                    pilihanTersimpan.delete(checkbox.value);
                }
                pesanPilihSantri.classList.add('d-none');
                perbaruiPilihan();
            });
        });

        checkboxHalaman.addEventListener('change', () => {
            checkboxSantri.forEach((checkbox) => {
                if (checkboxHalaman.checked) {
                    pilihanTersimpan.add(checkbox.value);
                } else {
                    pilihanTersimpan.delete(checkbox.value);
                }
            });
            perbaruiPilihan();
        });

        checkboxSemua.addEventListener('change', perbaruiPilihan);
        perbaruiPilihan();

        formUnduhPilihan.addEventListener('submit', (event) => {
            if (checkboxSemua.checked) return;
            if (pilihanTersimpan.size === 0) {
                event.preventDefault();
                pesanPilihSantri.classList.remove('d-none');
                return;
            }

            const terpilihDiHalaman = new Set(
                checkboxSantri.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value)
            );
            const idsTersembunyi = new Set(
                Array.from(formUnduhPilihan.querySelectorAll('input[type="hidden"][name="santri_ids[]"]'))
                    .map((input) => input.value)
            );
            pilihanTersimpan.forEach((id) => {
                if (terpilihDiHalaman.has(id) || idsTersembunyi.has(id)) return;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'santri_ids[]';
                input.value = id;
                formUnduhPilihan.appendChild(input);
                idsTersembunyi.add(id);
            });
        });
    }
</script>
@endpush
