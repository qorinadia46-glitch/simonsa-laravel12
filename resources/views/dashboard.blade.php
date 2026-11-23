@extends('layouts.app')

@section('judul', 'Dashboard')
@section('subjudul', 'Ringkasan ketertiban santri hari ini')

@section('content')
    <div class="stat-grid">
        <article class="stat hijau">
            <div>
                <span>Santri aktif</span>
                <b>{{ $jumlahSantri }}</b>
                <a href="{{ route('santri.index') }}">Lihat data</a>
            </div>
            <div class="stat-ikon"><i class="bi bi-people"></i></div>
        </article>
        <article class="stat kuning">
            <div>
                <span>Total pelanggaran</span>
                <b>{{ $jumlahPelanggaran }}</b>
                <a href="{{ route('pelanggaran.index') }}">Lihat data</a>
            </div>
            <div class="stat-ikon"><i class="bi bi-journal-x"></i></div>
        </article>
        <article class="stat biru">
            <div>
                <span>Pelanggaran bulan ini</span>
                <b>{{ $pelanggaranBulanIni }}</b>
                <small>{{ now()->translatedFormat('F Y') }}</small>
            </div>
            <div class="stat-ikon"><i class="bi bi-calendar-event"></i></div>
        </article>
        <article class="stat hitam">
            <div>
                <span>Akumulasi poin</span>
                <b>{{ $totalPoin }}</b>
                <a href="{{ route('poin.index') }}">Lihat poin</a>
            </div>
            <div class="stat-ikon"><i class="bi bi-123"></i></div>
        </article>
        <article class="stat oranye">
            <div>
                <span>Surat peringatan</span>
                <b>{{ $jumlahSurat }}</b>
                @if(auth()->user()->petugas())<a href="{{ route('surat.index') }}">Kelola surat</a>@else<small>Diterbitkan petugas</small>@endif
            </div>
            <div class="stat-ikon"><i class="bi bi-envelope-paper"></i></div>
        </article>
        <article class="stat ungu">
            <div>
                <span>Ta'zir belum selesai</span>
                <b>{{ $tazirBerjalan }}</b>
                @if(auth()->user()->petugas())<a href="{{ route('tazir.index') }}">Lihat ta'zir</a>@else<small>Dipantau keamanan</small>@endif
            </div>
            <div class="stat-ikon"><i class="bi bi-exclamation-diamond"></i></div>
        </article>
        <article class="stat merah">
            <div>
                <span>Santri mencapai SP</span>
                <b>{{ $jumlahSp }}</b>
                <a href="{{ route('poin.index') }}">Lihat akumulasi</a>
            </div>
            <div class="stat-ikon"><i class="bi bi-flag"></i></div>
        </article>
        <article class="stat toska">
            <div>
                <span>Notifikasi belum dibaca</span>
                <b>{{ $notifBelum }}</b>
                @if(auth()->user()->petugas())<a href="{{ route('notifikasi.index') }}">Buka notifikasi</a>@else<small>Untuk petugas</small>@endif
            </div>
            <div class="stat-ikon"><i class="bi bi-bell"></i></div>
        </article>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="kartu kartu-aksen hijau">
                <div class="kartu-kepala">Pelanggaran 6 bulan terakhir</div>
                <div class="kartu-isi"><div class="chart-box"><canvas id="grafikBulan"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="kartu kartu-aksen hitam h-100">
                <div class="kartu-kepala">Sedang masuk</div>
                <div class="kartu-isi">
                    <div class="profil-nama">{{ auth()->user()->nama_user }}</div>
                    <div class="mb-2">{{ auth()->user()->namaRole() }} · {{ auth()->user()->username }}</div>
                    <div class="catatan">Keputusan sanksi dilakukan di luar sistem oleh pengasuh pondok. Aplikasi mencatat poin, surat peringatan, dan pelaksanaan ta'zir.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-5">
            <div class="kartu kartu-aksen merah">
                <div class="kartu-kepala">Perlu perhatian</div>
                <div class="kartu-isi">
                    <div class="table-responsive mt-2">
                        <table class="table">
                            <thead><tr><th>Santri</th><th>Poin</th><th>Tingkat</th></tr></thead>
                            <tbody>
                            @forelse($santriSp as $santri)
                                <tr>
                                    <td><a href="{{ route('santri.show', $santri) }}">{{ $santri->nama_santri }}</a><div class="text-secondary small">{{ $santri->kelas }}</div></td>
                                    <td>{{ $santri->poinTerkumpul() }}</td>
                                    <td>@include('partials.tingkat', ['tingkat' => $santri->tingkatSaatIni()])</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="kosong">Belum ada santri yang mencapai batas SP.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="kartu kartu-aksen kuning">
                <div class="kartu-kepala">
                    <span>Pelanggaran terbaru</span>
                    <a href="{{ route('riwayat.index') }}">Riwayat</a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Tanggal</th><th>Santri</th><th>Pelanggaran</th><th>Poin</th></tr></thead>
                        <tbody>
                        @forelse($terbaru as $catatan)
                            @php
                                $terakhir = $catatan->first();
                                $kategoriRingkas = $catatan->pluck('kategori.nama_pelanggaran')->filter()->unique()->values();
                            @endphp
                            <tr>
                                <td>{{ $terakhir->tanggal_pelanggaran->translatedFormat('d M Y') }}</td>
                                <td><a href="{{ route('santri.show', $terakhir->santri) }}">{{ $terakhir->santri->nama_santri }}</a></td>
                                <td>
                                    <strong>{{ $catatan->count() }} pelanggaran</strong>
                                    <small class="text-secondary d-block">{{ $kategoriRingkas->join(' · ') }}</small>
                                </td>
                                <td>{{ $catatan->sum('poin_pelanggaran') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="kosong">Belum ada pelanggaran.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
    const warna = ['#1b8f4a', '#e0a11a', '#c2410c', '#b42318', '#14657a', '#4c3bb5', '#1aa58a', '#d4537e'];
    const bulan = @json($nilaiBulan);
    new Chart(document.getElementById('grafikBulan'), {
        type: 'bar',
        data: {
            labels: @json($labelBulan),
            datasets: [{
                label: 'Pelanggaran',
                data: bulan,
                backgroundColor: bulan.map((_, i) => warna[i % warna.length]),
                borderRadius: 8
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>
@endpush
