<?php

namespace App\Exports;

use App\Models\Santri;
use App\Services\PoinService;
use App\Services\RekapService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanPelanggaranExport implements Export, WithMultipleSheets
{
    public function __construct(
        private array $filter,
        private RekapService $rekap
    ) {
    }

    public function sheets(): array
    {
        $ekspresiBulan = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', pelanggaran.tanggal_pelanggaran)"
            : "DATE_FORMAT(pelanggaran.tanggal_pelanggaran, '%Y-%m')";

        $bulanan = $this->rekap->query($this->filter)
            ->selectRaw(
                $ekspresiBulan.' as bulan, COUNT(pelanggaran.id_pelanggaran) as jumlah_kasus, SUM(pelanggaran.poin_pelanggaran) as total_poin, COUNT(DISTINCT pelanggaran.id_santri) as jumlah_santri'
            )
            ->groupBy(DB::raw($ekspresiBulan))
            ->orderBy('bulan')
            ->get()
            ->map(fn ($baris) => [
                $baris->bulan,
                (int) $baris->jumlah_kasus,
                (int) $baris->total_poin,
                (int) $baris->jumlah_santri,
            ])
            ->all();

        $kategori = $this->rekap->query($this->filter)
            ->join('kategori_pelanggaran', 'kategori_pelanggaran.id_kategori', '=', 'pelanggaran.id_kategori')
            ->select('kategori_pelanggaran.kode_pelanggaran', 'kategori_pelanggaran.nama_pelanggaran')
            ->selectRaw(
                'COUNT(pelanggaran.id_pelanggaran) as jumlah_kasus, SUM(pelanggaran.poin_pelanggaran) as total_poin, COUNT(DISTINCT pelanggaran.id_santri) as jumlah_santri'
            )
            ->groupBy(
                'kategori_pelanggaran.id_kategori',
                'kategori_pelanggaran.kode_pelanggaran',
                'kategori_pelanggaran.nama_pelanggaran'
            )
            ->orderByDesc('jumlah_kasus')
            ->orderBy('kategori_pelanggaran.nama_pelanggaran')
            ->get()
            ->map(fn ($baris) => [
                $baris->kode_pelanggaran,
                $baris->nama_pelanggaran,
                (int) $baris->jumlah_kasus,
                (int) $baris->total_poin,
                (int) $baris->jumlah_santri,
            ])
            ->all();

        $santri = $this->rekap->query($this->filter)
            ->join('santri', 'santri.id_santri', '=', 'pelanggaran.id_santri')
            ->select('santri.nis', 'santri.nama_santri', 'santri.kelas')
            ->selectRaw(
                'COUNT(pelanggaran.id_pelanggaran) as jumlah_kasus, SUM(pelanggaran.poin_pelanggaran) as total_poin'
            )
            ->groupBy('santri.id_santri', 'santri.nis', 'santri.nama_santri', 'santri.kelas')
            ->orderByDesc('jumlah_kasus')
            ->orderByDesc('total_poin')
            ->orderBy('santri.nama_santri')
            ->get()
            ->map(fn ($baris) => [
                $baris->nis,
                $baris->nama_santri,
                $baris->kelas,
                (int) $baris->jumlah_kasus,
                (int) $baris->total_poin,
            ])
            ->all();

        [$daftarSp, $jumlahSp] = $this->rekapSp();

        return [
            new DetailPelanggaranSheet($this->filter, $this->rekap),
            new TabelLaporanSheet('Rekap Bulanan', [
                'Bulan',
                'Jumlah Kasus',
                'Total Poin',
                'Santri Terlibat',
            ], $bulanan),
            new TabelLaporanSheet('Per Kategori', [
                'Kode Kategori',
                'Kategori Pelanggaran',
                'Jumlah Kasus',
                'Total Poin',
                'Santri Terlibat',
            ], $kategori),
            new TabelLaporanSheet('Peringkat Santri', [
                'NIS',
                'Nama Santri',
                'Kelas',
                'Jumlah Pelanggaran',
                'Total Poin',
            ], $santri),
            new TabelLaporanSheet('Daftar SP Saat Ini', [
                'NIS',
                'Nama Santri',
                'Kelas',
                'Akumulasi Poin Semua Riwayat',
                'Tingkat SP Saat Ini',
            ], $daftarSp),
            new TabelLaporanSheet('Rekap SP', [
                'Tingkat',
                'Santri Tepat di Tingkat',
                'Santri Mencapai atau Melewati Tingkat',
                'Batas Poin',
            ], $jumlahSp),
        ];
    }

    private function rekapSp(): array
    {
        $batas = PoinService::batas();
        $jumlahTepat = ['SP1' => 0, 'SP2' => 0, 'SP3' => 0];
        $jumlahMencapai = ['SP1' => 0, 'SP2' => 0, 'SP3' => 0];
        $daftar = [];

        $santri = Santri::query()
            ->withSum('pelanggaran', 'poin_pelanggaran')
            ->orderBy('nama_santri')
            ->get();

        foreach ($santri as $item) {
            $poin = (int) $item->pelanggaran_sum_poin_pelanggaran;
            $tingkat = PoinService::tingkat($poin);

            if ($tingkat) {
                $jumlahTepat[$tingkat]++;
                $daftar[] = [
                    $item->nis,
                    $item->nama_santri,
                    $item->kelas,
                    $poin,
                    $tingkat,
                ];
            }

            foreach (['SP1' => 'sp1', 'SP2' => 'sp2', 'SP3' => 'sp3'] as $label => $kunci) {
                if ($batas[$kunci] > 0 && $poin >= $batas[$kunci]) {
                    $jumlahMencapai[$label]++;
                }
            }
        }

        $urutan = ['SP1' => 1, 'SP2' => 2, 'SP3' => 3];
        usort($daftar, fn ($a, $b) => ($urutan[$b[4]] <=> $urutan[$a[4]]) ?: ($b[3] <=> $a[3]));

        $ringkasan = [];
        foreach (['SP1' => 'sp1', 'SP2' => 'sp2', 'SP3' => 'sp3'] as $label => $kunci) {
            $ringkasan[] = [$label, $jumlahTepat[$label], $jumlahMencapai[$label], $batas[$kunci]];
        }

        return [$daftar, $ringkasan];
    }
}
