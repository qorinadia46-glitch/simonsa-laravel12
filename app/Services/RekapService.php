<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Pelanggaran;
use App\Models\Santri;
use App\Models\SuratPeringatan;
use App\Models\Tazir;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RekapService
{
    public function dasbor(): array
    {
        $batas = PoinService::batas()['sp1'];
        $chart = $this->grafik([]);
        $peringkat = DB::table('pelanggaran')
            ->select('id_santri', DB::raw('SUM(poin_pelanggaran) as total'))
            ->groupBy('id_santri')
            ->havingRaw('SUM(poin_pelanggaran) >= ?', [$batas])
            ->orderByDesc('total')
            ->get();

        $santri = Santri::whereIn('id_santri', $peringkat->pluck('id_santri'))->get()->keyBy('id_santri');
        $santriSp = $peringkat->map(function ($baris) use ($santri) {
            $item = $santri->get($baris->id_santri);
            if (! $item) {
                return null;
            }
            $item->setAttribute('pelanggaran_sum_poin_pelanggaran', (int) $baris->total);

            return $item;
        })->filter()->values();

        $user = auth()->user();
        $notif = Notifikasi::where('status_baca', 'belum');
        if ($user && ! $user->admin()) {
            $notif->where('id_user', $user->id_user);
        }

        return [
            'jumlahSantri' => Santri::where('status', 'aktif')->count(),
            'jumlahPelanggaran' => Pelanggaran::count(),
            'pelanggaranBulanIni' => Pelanggaran::whereBetween('tanggal_pelanggaran', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])->count(),
            'totalPoin' => (int) Pelanggaran::sum('poin_pelanggaran'),
            'jumlahSurat' => SuratPeringatan::count(),
            'tazirBerjalan' => Tazir::whereIn('status_pelaksanaan', ['menunggu', 'berjalan'])->count(),
            'jumlahSp' => $santriSp->count(),
            'notifBelum' => $user && $user->petugas() ? $notif->count() : 0,
            'santriSp' => $santriSp,
            'terbaru' => Pelanggaran::with(['santri', 'kategori', 'pengguna'])
                ->orderByDesc('tanggal_pelanggaran')
                ->orderByDesc('id_pelanggaran')
                ->limit(6)
                ->get()
                ->groupBy('id_santri'),
            'labelBulan' => $chart['labelBulan'],
            'nilaiBulan' => $chart['nilaiBulan'],
            'labelKategori' => $chart['labelKategori'],
            'nilaiKategori' => $chart['nilaiKategori'],
        ];
    }

    public function grafik(array $filter): array
    {
        $awal = ! empty($filter['dari'])
            ? Carbon::parse($filter['dari'])->startOfMonth()
            : now()->startOfMonth()->subMonths(5);
        $akhir = ! empty($filter['sampai'])
            ? Carbon::parse($filter['sampai'])->endOfMonth()
            : now()->endOfMonth();

        $expr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', pelanggaran.tanggal_pelanggaran)"
            : "DATE_FORMAT(pelanggaran.tanggal_pelanggaran, '%Y-%m')";

        $dasar = $this->query($filter)
            ->whereBetween('tanggal_pelanggaran', [$awal->toDateString(), $akhir->toDateString()]);

        $baris = (clone $dasar)
            ->selectRaw($expr.' as bulan, COUNT(*) as jumlah')
            ->groupBy(DB::raw($expr))
            ->pluck('jumlah', 'bulan');

        $label = [];
        $nilai = [];
        $cursor = $awal->copy()->startOfMonth();
        $batasAkhir = $akhir->copy()->startOfMonth();
        $jaga = 0;
        while ($cursor <= $batasAkhir && $jaga < 36) {
            $kunci = $cursor->format('Y-m');
            $label[] = $cursor->copy()->locale('id')->translatedFormat('M Y');
            $nilai[] = (int) ($baris[$kunci] ?? 0);
            $cursor->addMonth();
            $jaga++;
        }

        $kategori = (clone $dasar)
            ->join('kategori_pelanggaran', 'kategori_pelanggaran.id_kategori', '=', 'pelanggaran.id_kategori')
            ->selectRaw('kategori_pelanggaran.nama_pelanggaran as nama, COUNT(*) as jumlah')
            ->groupBy('kategori_pelanggaran.nama_pelanggaran')
            ->orderByDesc('jumlah')
            ->get();

        return [
            'labelBulan' => $label,
            'nilaiBulan' => $nilai,
            'labelKategori' => $kategori->pluck('nama')->all(),
            'nilaiKategori' => $kategori->pluck('jumlah')->map(fn ($n) => (int) $n)->all(),
        ];
    }

    public function query(array $filter)
    {
        $query = Pelanggaran::query();

        if (! empty($filter['dari'])) {
            $query->whereDate('tanggal_pelanggaran', '>=', $filter['dari']);
        }
        if (! empty($filter['sampai'])) {
            $query->whereDate('tanggal_pelanggaran', '<=', $filter['sampai']);
        }
        if (! empty($filter['id_kategori'])) {
            $query->where('pelanggaran.id_kategori', $filter['id_kategori']);
        }
        if (! empty($filter['kelas'])) {
            $query->whereHas('santri', fn ($santri) => $santri->where('kelas', $filter['kelas']));
        }
        if (! empty($filter['q'])) {
            $kata = $filter['q'];
            $query->where(function ($w) use ($kata) {
                $w->where('kronologi', 'like', "%{$kata}%")
                    ->orWhereHas('santri', function ($santri) use ($kata) {
                        $santri->where('nama_santri', 'like', "%{$kata}%")
                            ->orWhere('nis', 'like', "%{$kata}%");
                    });
            });
        }

        return $query;
    }
}
