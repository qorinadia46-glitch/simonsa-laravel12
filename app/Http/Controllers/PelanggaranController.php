<?php

namespace App\Http\Controllers;

use App\Models\KategoriPelanggaran;
use App\Models\Pelanggaran;
use App\Models\Santri;
use App\Services\PoinService;
use App\Services\RekapService;
use App\Support\Validasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PelanggaranController extends Controller
{
    public function index(Request $request, RekapService $rekap)
    {
        $filter = $request->only(['q', 'id_kategori', 'kelas', 'dari', 'sampai']);
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
            ->paginate(10)
            ->withQueryString();

        $kategori = KategoriPelanggaran::orderBy('nama_pelanggaran')->get();
        $kelas = Santri::query()->select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');

        return view('pelanggaran.index', compact('data', 'kategori', 'kelas', 'filter'));
    }

    public function riwayat(Request $request, RekapService $rekap)
    {
        $filter = $request->only(['q', 'kelas']);
        $data = $rekap->query($filter)
            ->with(['santri', 'kategori', 'pengguna'])
            ->orderByDesc('tanggal_pelanggaran')
            ->orderByDesc('id_pelanggaran')
            ->paginate(12)
            ->withQueryString();
        $kelas = Santri::query()->select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');

        return view('pelanggaran.riwayat', compact('data', 'kelas', 'filter'));
    }

    public function create()
    {
        $santri = Santri::where('status', 'aktif')->orderBy('nama_santri')->get();
        $kategori = KategoriPelanggaran::where('status', 'aktif')->orderBy('nama_pelanggaran')->get();

        return view('pelanggaran.form', compact('santri', 'kategori'));
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request, true);
        $foto = $request->file('bukti_foto');
        if ($foto) {
            $data['bukti_foto'] = $foto->store('bukti-pelanggaran', 'public');
        }
        $hasil = PoinService::catat($data, $request->user());

        return $this->setelahSimpan($hasil, 'Pelanggaran berhasil dicatat.');
    }

    public function show(Pelanggaran $pelanggaran)
    {
        $pelanggaran->load(['santri', 'kategori', 'pengguna', 'tazir.pengguna']);
        $pelanggaran->santri->loadSum('pelanggaran', 'poin_pelanggaran');

        return view('pelanggaran.show', compact('pelanggaran'));
    }

    public function edit(Pelanggaran $pelanggaran)
    {
        $santri = Santri::orderBy('nama_santri')->get();
        $kategori = KategoriPelanggaran::orderBy('nama_pelanggaran')->get();

        return view('pelanggaran.form', compact('pelanggaran', 'santri', 'kategori'));
    }

    public function update(Request $request, Pelanggaran $pelanggaran)
    {
        $data = $this->validasi($request, false);
        $foto = $request->file('bukti_foto');
        $fotoLama = $pelanggaran->bukti_foto;
        if ($foto) {
            $data['bukti_foto'] = $foto->store('bukti-pelanggaran', 'public');
        }
        $hasil = PoinService::perbarui($pelanggaran, $data);
        if ($foto && $fotoLama) {
            Storage::disk('public')->delete($fotoLama);
        }

        return $this->setelahSimpan($hasil, 'Pelanggaran berhasil diperbarui.');
    }

    public function destroy(Pelanggaran $pelanggaran)
    {
        if ($pelanggaran->tazir()->exists()) {
            return back()->with('gagal', 'Hapus data ta\'zir yang terkait terlebih dahulu.');
        }
        if ($pelanggaran->bukti_foto) {
            Storage::disk('public')->delete($pelanggaran->bukti_foto);
        }
        $pelanggaran->delete();

        return redirect()->route('pelanggaran.index')->with('sukses', 'Pelanggaran berhasil dihapus.');
    }

    private function validasi(Request $request, bool $baru): array
    {
        return $request->validate([
            'id_santri' => ['required', 'integer', 'exists:santri,id_santri'],
            'id_kategori' => ['required', 'integer', 'exists:kategori_pelanggaran,id_kategori'],
            'tanggal_pelanggaran' => ['required', 'date', 'before_or_equal:today'],
            'kronologi' => ['required', 'string'],
            'tindak_lanjut' => ['nullable', 'string'],
            'bukti_foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], Validasi::pesan(), [
            'id_santri' => 'santri',
            'id_kategori' => 'kategori',
            'tanggal_pelanggaran' => 'tanggal',
            'kronologi' => 'kronologi',
            'tindak_lanjut' => 'tindak lanjut',
        ]);
    }

    private function setelahSimpan(array $hasil, string $pesan)
    {
        $redirect = redirect()->route('pelanggaran.show', $hasil['pelanggaran'])->with('sukses', $pesan);
        if ($hasil['naik']) {
            $redirect->with('peringatan', 'Akumulasi poin mencapai '.$hasil['tingkat'].' ('.$hasil['total'].' poin). Terbitkan surat peringatan.')
                ->with('tautan_sp', route('surat.create', [
                    'santri' => $hasil['pelanggaran']->id_santri,
                    'tingkat' => $hasil['tingkat'],
                ]));
        }

        return $redirect;
    }
}
