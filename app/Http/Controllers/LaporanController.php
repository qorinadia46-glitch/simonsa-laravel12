<?php

namespace App\Http\Controllers;

use App\Exports\LaporanPelanggaranExport;
use App\Exports\LaporanPilihanExport;
use App\Models\KategoriPelanggaran;
use App\Models\Santri;
use App\Services\PoinService;
use App\Services\LaporanPilihanService;
use App\Services\RekapService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(
        Request $request,
        RekapService $rekap,
        LaporanPilihanService $laporanPilihan
    )
    {
        if ($request->user()->role === 'keamanan') {
            abort(403);
        }

        $jenisLaporan = $request->query('jenis');
        abort_unless(
            $jenisLaporan === null
                || (is_string($jenisLaporan) && array_key_exists($jenisLaporan, LaporanPilihanService::JENIS)),
            404
        );
        if ($jenisLaporan !== null && ! $request->user()->admin()) {
            abort(403);
        }

        $filter = $request->only(['q', 'id_kategori', 'kelas', 'dari', 'sampai']);
        $pilihanLaporan = LaporanPilihanService::JENIS;
        $kategori = KategoriPelanggaran::orderBy('nama_pelanggaran')->get();
        $kelas = Santri::query()->select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $data = $chart = $ringkas = $batas = $pratinjau = null;

        if ($jenisLaporan === null) {
            $data = Santri::query()
                ->whereIn('id_santri', $rekap->query($filter)
                    ->select('pelanggaran.id_santri')
                    ->distinct())
                ->with(['pelanggaran' => function ($query) use ($filter, $rekap) {
                    $query->whereIn('id_pelanggaran', $rekap->query($filter)
                        ->select('pelanggaran.id_pelanggaran'))
                        ->with(['kategori', 'pengguna'])
                        ->orderByDesc('tanggal_pelanggaran')
                        ->orderByDesc('id_pelanggaran');
                }])
                ->orderBy('nama_santri')
                ->paginate(12)
                ->withQueryString();

            $chart = $rekap->grafik($filter);
            $ringkas = [
                'jumlah' => $rekap->query($filter)->count(),
                'poin' => (int) $rekap->query($filter)->sum('poin_pelanggaran'),
                'santri' => $rekap->query($filter)->distinct('id_santri')->count('id_santri'),
            ];
            $batas = PoinService::batas();
        } else {
            $pratinjau = $laporanPilihan->pratinjau($jenisLaporan, $filter);
        }

        return view('laporan.index', compact(
            'data',
            'chart',
            'kategori',
            'kelas',
            'filter',
            'ringkas',
            'batas',
            'jenisLaporan',
            'pilihanLaporan',
            'pratinjau'
        ));
    }

    public function catatCetak()
    {
        return response()->noContent();
    }

    public function unduh(Request $request, RekapService $rekap)
    {
        $filter = $request->only(['q', 'id_kategori', 'kelas', 'dari', 'sampai']);
        $namaFile = 'laporan-pelanggaran-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new LaporanPelanggaranExport($filter, $rekap), $namaFile);
    }

    public function unduhPilihan(Request $request, LaporanPilihanService $laporanPilihan)
    {
        $request->validate([
            'jenis' => ['required', Rule::in(array_keys(LaporanPilihanService::JENIS))],
            'santri_ids' => ['sometimes', 'array', 'max:1000'],
            'santri_ids.*' => ['required', 'integer', 'distinct'],
            'semua_santri' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
            'id_kategori' => ['nullable', 'integer', 'exists:kategori_pelanggaran,id_kategori'],
            'kelas' => ['nullable', 'string', 'max:30'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $jenis = $request->string('jenis')->toString();
        $filter = $request->only(['q', 'id_kategori', 'kelas', 'dari', 'sampai']);
        $santriIds = $laporanPilihan->resolveSantriIds(
            $jenis,
            $filter,
            $request->input('santri_ids', []),
            $request->boolean('semua_santri')
        );
        [$kolom, $baris] = $laporanPilihan->dataUnduhan($jenis, $filter, $santriIds);

        return Excel::download(
            new LaporanPilihanExport($kolom, $baris),
            'laporan-'.$jenis.'-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}
