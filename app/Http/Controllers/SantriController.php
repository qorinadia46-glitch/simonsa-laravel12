<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Support\Validasi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SantriController extends Controller
{
    public function index(Request $request)
    {
        $kata = $request->string('q')->toString();
        $data = Santri::query()
            ->denganPoin()
            ->when($kata, function ($query) use ($kata) {
                $query->where(function ($w) use ($kata) {
                    $w->where('nama_santri', 'like', "%{$kata}%")
                        ->orWhere('nis', 'like', "%{$kata}%")
                        ->orWhere('kamar', 'like', "%{$kata}%");
                });
            })
            ->when($request->filled('kelas'), fn ($query) => $query->where('kelas', $request->kelas))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderBy('nama_santri')
            ->paginate(10)
            ->withQueryString();

        $kelas = Santri::query()->select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');

        return view('santri.index', compact('data', 'kata', 'kelas'));
    }

    public function create()
    {
        return view('santri.form');
    }

    public function store(Request $request)
    {
        Santri::create($this->validasi($request));

        return redirect()->route('santri.index')->with('sukses', 'Data santri berhasil disimpan.');
    }

    public function show(Santri $santri)
    {
        $santri->loadSum('pelanggaran', 'poin_pelanggaran');
        $pelanggaran = $santri->pelanggaran()->with(['kategori', 'pengguna', 'tazir'])->orderByDesc('tanggal_pelanggaran')->get();
        $surat = $santri->suratPeringatan()->with('pengguna')->orderByDesc('tanggal_surat')->get();

        return view('santri.show', compact('santri', 'pelanggaran', 'surat'));
    }

    public function edit(Santri $santri)
    {
        return view('santri.form', compact('santri'));
    }

    public function update(Request $request, Santri $santri)
    {
        $santri->update($this->validasi($request, $santri));

        return redirect()->route('santri.show', $santri)->with('sukses', 'Data santri berhasil diperbarui.');
    }

    public function destroy(Santri $santri)
    
{
    // Jangan hapus santri jika masih memiliki riwayat pelanggaran
    if ($santri->pelanggaran()->exists()) {
        return back()->with(
            'gagal',
            'Santri masih memiliki riwayat pelanggaran. Hapus atau arsipkan riwayatnya terlebih dahulu.'
        );
    }

    // Jangan hapus santri jika masih memiliki surat peringatan
    if ($santri->suratPeringatan()->exists()) {
        return back()->with(
            'gagal',
            'Santri masih memiliki surat peringatan. Hapus atau arsipkan suratnya terlebih dahulu.'
        );
    }

    try {
        \Illuminate\Support\Facades\DB::transaction(function () use ($santri) {
            // Hapus notifikasi milik santri ini saja
            $santri->notifikasi()->delete();

            // Hapus data santri
            $santri->delete();
        });

        return redirect()->route('santri.index')->with(
            'sukses',
            'Data santri dan notifikasi terkait berhasil dihapus.'
        );

    } catch (\Throwable $e) {
        return back()->with(
            'gagal',
            'Data santri gagal dihapus: ' . $e->getMessage()
        );
    }
}


    private function validasi(Request $request, ?Santri $santri = null): array
    {
        return $request->validate([
            'nis' => ['required', 'string', 'max:20', Rule::unique('santri', 'nis')->ignore($santri?->id_santri, 'id_santri')],
            'nama_santri' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'alamat' => ['nullable', 'string'],
            'kamar' => ['nullable', 'string', 'max:50'],
            'kelas' => ['nullable', 'string', 'max:30'],
            'tanggal_masuk' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', 'in:aktif,nonaktif,alumni'],
        ], Validasi::pesan(), [
            'nis' => 'NIS',
            'nama_santri' => 'nama santri',
            'jenis_kelamin' => 'jenis kelamin',
            'alamat' => 'alamat',
            'kamar' => 'kamar',
            'kelas' => 'kelas',
            'tanggal_masuk' => 'tanggal masuk',
            'status' => 'status',
        ]);
    }
}
