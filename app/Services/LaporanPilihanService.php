<?php

namespace App\Services;

use App\Models\Pelanggaran;
use App\Models\Santri;
use App\Models\SuratPeringatan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class LaporanPilihanService
{
    public const JENIS = [
        'data-santri' => 'Data Santri',
        'santri-melanggar' => 'Data Santri yang Melanggar',
        'ringan' => 'Data Pelanggaran Ringan',
        'sedang' => 'Data Pelanggaran Sedang',
        'berat' => 'Data Santri Pelanggaran Berat',
        'seluruh-pelanggaran' => 'Rekap Seluruh Pelanggaran',
        'akumulasi-poin' => 'Rekap Akumulasi Poin',
        'surat-peringatan' => 'Rekap Surat Peringatan',
    ];

    public function __construct(private RekapService $rekap)
    {
    }

    public function pratinjau(string $jenis, array $filter): LengthAwarePaginator
    {
        $query = $this->querySantri($jenis, $filter);

        if ($this->memilikiPelanggaran($jenis)) {
            $filterPelanggaran = fn ($query) => $this->filterPelanggaran($query, $jenis, $filter);

            $query
                ->with([
                    'pelanggaran' => fn ($query) => $filterPelanggaran($query)
                        ->with(['kategori', 'pengguna'])
                        ->orderByDesc('tanggal_pelanggaran')
                        ->orderByDesc('id_pelanggaran'),
                ])
                ->withCount(['pelanggaran as jumlah_pelanggaran' => $filterPelanggaran])
                ->withSum(['pelanggaran as total_poin' => $filterPelanggaran], 'poin_pelanggaran');
        } else {
            $query
                ->withCount('pelanggaran')
                ->withSum('pelanggaran as total_poin', 'poin_pelanggaran');
        }

        if ($jenis === 'surat-peringatan') {
            $query
                ->withCount(['suratPeringatan' => fn (Builder $query) => $this->filterSurat($query, $filter)])
                ->with(['suratPeringatan' => function ($query) use ($filter) {
                    $this->filterSurat($query, $filter);
                    $query->with('pengguna')->orderByDesc('tanggal_surat')->orderByDesc('id_surat');
                }]);
        }

        return $query->orderBy('nama_santri')->paginate(20)->withQueryString();
    }

    public function resolveSantriIds(
        string $jenis,
        array $filter,
        array $requestedIds,
        bool $semua
    ): array {
        $query = $this->querySantri($jenis, $filter);

        if ($semua) {
            $ids = $query->pluck('id_santri')->all();
        } else {
            $requestedIds = array_values(array_unique(array_map('intval', $requestedIds)));
            if ($requestedIds === []) {
                throw ValidationException::withMessages([
                    'santri_ids' => 'Pilih satu atau beberapa santri, atau centang semua hasil.',
                ]);
            }

            $ids = (clone $query)
                ->whereIn('santri.id_santri', $requestedIds)
                ->pluck('id_santri')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($ids) !== count($requestedIds)) {
                throw ValidationException::withMessages([
                    'santri_ids' => 'Pilihan santri tidak sesuai dengan data pratinjau.',
                ]);
            }
        }

        if ($ids === []) {
            throw ValidationException::withMessages([
                'santri_ids' => 'Tidak ada data santri yang dapat diunduh pada pilihan ini.',
            ]);
        }

        return $ids;
    }

    public function dataUnduhan(string $jenis, array $filter, array $santriIds): array
    {
        if ($jenis === 'data-santri') {
            $santri = Santri::query()
                ->whereIn('id_santri', $santriIds)
                ->orderBy('nama_santri')
                ->get();

            return [
                ['NIS', 'Nama Santri', 'Jenis Kelamin', 'Kelas', 'Kamar', 'Alamat', 'Status'],
                $santri->map(fn (Santri $item) => [
                    $item->nis,
                    $item->nama_santri,
                    $item->jenis_kelamin,
                    $item->kelas,
                    $item->kamar,
                    $item->alamat,
                    $item->status,
                ])->all(),
            ];
        }

        if ($jenis === 'akumulasi-poin') {
            $santri = Santri::query()
                ->whereIn('id_santri', $santriIds)
                ->withSum('pelanggaran', 'poin_pelanggaran')
                ->orderBy('nama_santri')
                ->get();

            return [
                ['NIS', 'Nama Santri', 'Kelas', 'Total Poin', 'Tingkat SP'],
                $santri->map(fn (Santri $item) => [
                    $item->nis,
                    $item->nama_santri,
                    $item->kelas,
                    (int) $item->pelanggaran_sum_poin_pelanggaran,
                    PoinService::tingkat((int) $item->pelanggaran_sum_poin_pelanggaran) ?? '-',
                ])->all(),
            ];
        }

        if ($jenis === 'surat-peringatan') {
            $surat = SuratPeringatan::query()
                ->with(['santri', 'pengguna'])
                ->whereIn('id_santri', $santriIds)
                ->when(! empty($filter['dari']), fn ($query) => $query->whereDate('tanggal_surat', '>=', $filter['dari']))
                ->when(! empty($filter['sampai']), fn ($query) => $query->whereDate('tanggal_surat', '<=', $filter['sampai']))
                ->orderByDesc('tanggal_surat')
                ->orderByDesc('id_surat')
                ->get();

            return [
                ['Tanggal Surat', 'Nomor Surat', 'NIS', 'Nama Santri', 'Tingkat', 'Total Poin', 'Isi Surat', 'Petugas'],
                $surat->map(fn (SuratPeringatan $item) => [
                    $item->tanggal_surat?->format('Y-m-d'),
                    $item->nomor_surat,
                    $item->santri->nis,
                    $item->santri->nama_santri,
                    $item->tingkat_peringatan,
                    $item->total_poin,
                    $item->isi_surat,
                    $item->pengguna?->nama_user ?? '-',
                ])->all(),
            ];
        }

        if ($jenis === 'santri-melanggar') {
            $santri = $this->querySantri($jenis, $filter)
                ->whereIn('santri.id_santri', $santriIds)
                ->with([
                    'pelanggaran' => fn ($query) => $this->filterPelanggaran($query, $jenis, $filter)
                        ->with('kategori')
                        ->orderByDesc('tanggal_pelanggaran')
                        ->orderByDesc('id_pelanggaran'),
                ])
                ->withCount(['pelanggaran as jumlah_pelanggaran' => fn (Builder $query) => $this->filterPelanggaran($query, $jenis, $filter)])
                ->withSum(['pelanggaran as total_poin' => fn (Builder $query) => $this->filterPelanggaran($query, $jenis, $filter)], 'poin_pelanggaran')
                ->orderBy('nama_santri')
                ->get();

            return [
                ['NIS', 'Nama Santri', 'Kelas', 'Jumlah Pelanggaran', 'Total Poin', 'Pelanggaran Terakhir', 'Tanggal Terakhir', 'Poin Terakhir', 'Keterangan'],
                $santri->map(function (Santri $item) {
                    $terakhir = $item->pelanggaran->first();

                    return [
                        $item->nis,
                        $item->nama_santri,
                        $item->kelas,
                        $item->jumlah_pelanggaran,
                        (int) $item->total_poin,
                        $terakhir?->kategori?->nama_pelanggaran ?? '-',
                        $terakhir?->tanggal_pelanggaran?->format('Y-m-d') ?? '-',
                        $terakhir?->poin_pelanggaran ?? '-',
                        $terakhir?->kronologi ?? '-',
                    ];
                })->all(),
            ];
        }

        $pelanggaran = $this->queryPelanggaran($jenis, $filter)
            ->whereIn('pelanggaran.id_santri', $santriIds)
            ->with(['santri', 'kategori', 'pengguna'])
            ->orderByDesc('tanggal_pelanggaran')
            ->orderByDesc('id_pelanggaran')
            ->get();

        return [
            ['NIS', 'Nama Santri', 'Kelas', 'Tanggal Pelanggaran', 'Jenis Pelanggaran', 'Poin', 'Keterangan', 'Tindak Lanjut', 'Petugas'],
            $pelanggaran->map(fn (Pelanggaran $item) => [
                $item->santri->nis,
                $item->santri->nama_santri,
                $item->santri->kelas,
                $item->tanggal_pelanggaran->format('Y-m-d'),
                $item->kategori->nama_pelanggaran,
                $item->poin_pelanggaran,
                $item->kronologi,
                $item->tindak_lanjut,
                $item->pengguna?->nama_user ?? '-',
            ])->all(),
        ];
    }

    private function querySantri(string $jenis, array $filter): Builder
    {
        $query = Santri::query();

        if (! empty($filter['q'])) {
            $kata = $filter['q'];
            $query->where(function (Builder $query) use ($kata) {
                $query->where('nama_santri', 'like', "%{$kata}%")
                    ->orWhere('nis', 'like', "%{$kata}%");
            });
        }

        if (! empty($filter['kelas'])) {
            $query->where('kelas', $filter['kelas']);
        }

        if ($this->memilikiPelanggaran($jenis)) {
            $query->whereIn(
                'santri.id_santri',
                $this->queryPelanggaran($jenis, $filter)->select('pelanggaran.id_santri')->distinct()
            );
        }

        if ($jenis === 'surat-peringatan') {
            $query->whereHas('suratPeringatan', fn (Builder $query) => $this->filterSurat($query, $filter));
        }

        return $query;
    }

    private function queryPelanggaran(string $jenis, array $filter): Builder
    {
        unset($filter['q']);

        $query = $this->rekap->query($filter);

        return match ($jenis) {
            'ringan' => $query->whereBetween('pelanggaran.poin_pelanggaran', [1, 9]),
            'sedang' => $query->whereBetween('pelanggaran.poin_pelanggaran', [10, 24]),
            'berat' => $query->where('pelanggaran.poin_pelanggaran', '>=', 25),
            default => $query,
        };
    }

    private function filterPelanggaran(Builder|HasMany $query, string $jenis, array $filter): Builder|HasMany
    {
        foreach (['dari', 'sampai', 'id_kategori', 'kelas'] as $kunci) {
            if (! empty($filter[$kunci])) {
                if ($kunci === 'dari') {
                    $query->whereDate('tanggal_pelanggaran', '>=', $filter[$kunci]);
                } elseif ($kunci === 'sampai') {
                    $query->whereDate('tanggal_pelanggaran', '<=', $filter[$kunci]);
                } elseif ($kunci === 'id_kategori') {
                    $query->where('id_kategori', $filter[$kunci]);
                } elseif ($kunci === 'kelas') {
                    $query->whereHas('santri', fn (Builder $santri) => $santri->where('kelas', $filter[$kunci]));
                }
            }
        }

        return match ($jenis) {
            'ringan' => $query->whereBetween('poin_pelanggaran', [1, 9]),
            'sedang' => $query->whereBetween('poin_pelanggaran', [10, 24]),
            'berat' => $query->where('poin_pelanggaran', '>=', 25),
            default => $query,
        };
    }

    private function filterSurat(Builder|HasMany $query, array $filter): Builder|HasMany
    {
        if (! empty($filter['dari'])) {
            $query->whereDate('tanggal_surat', '>=', $filter['dari']);
        }
        if (! empty($filter['sampai'])) {
            $query->whereDate('tanggal_surat', '<=', $filter['sampai']);
        }

        return $query;
    }

    private function memilikiPelanggaran(string $jenis): bool
    {
        return in_array($jenis, [
            'santri-melanggar',
            'ringan',
            'sedang',
            'berat',
            'seluruh-pelanggaran',
        ], true);
    }
}
