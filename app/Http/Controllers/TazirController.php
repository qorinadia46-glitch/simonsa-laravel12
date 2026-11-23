<?php

namespace App\Http\Controllers;

use App\Models\Pelanggaran;
use App\Models\Tazir;
use App\Support\Validasi;
use Illuminate\Http\Request;

class TazirController extends Controller
{
    public function index(Request $request)
    {
        $kata = $request->string('q')->toString();
        $data = Tazir::with(['pelanggaran.santri', 'pelanggaran.kategori', 'pengguna'])
            ->when($kata, function ($query) use ($kata) {
                $query->where(function ($w) use ($kata) {
                    $w->where('jenis_tazir', 'like', "%{$kata}%")
                        ->orWhereHas('pelanggaran.santri', fn ($santri) => $santri->where('nama_santri', 'like', "%{$kata}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status_pelaksanaan', $request->status))
            ->orderByDesc('id_tazir')
            ->paginate(10)
            ->withQueryString();

        return view('tazir.index', compact('data', 'kata'));
    }

    public function create(Request $request)
    {
        $pelanggaran = Pelanggaran::with(['santri', 'kategori'])->orderByDesc('tanggal_pelanggaran')->get();
        $pilihan = $request->integer('pelanggaran');

        return view('tazir.form', compact('pelanggaran', 'pilihan'));
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['id_user'] = $request->user()->id_user;
        Tazir::create($data);

        return redirect()->route('tazir.index')->with('sukses', 'Pelaksanaan ta\'zir berhasil dicatat.');
    }

    public function edit(Tazir $tazir)
    {
        abort_unless(request()->user()?->admin(), 403, 'Hanya admin yang dapat mengubah data ta\'zir.');

        $pelanggaran = Pelanggaran::with(['santri', 'kategori'])->orderByDesc('tanggal_pelanggaran')->get();

        return view('tazir.form', compact('tazir', 'pelanggaran'));
    }

    public function update(Request $request, Tazir $tazir)
    {
        abort_unless($request->user()?->admin(), 403, 'Hanya admin yang dapat mengubah data ta\'zir.');

        $tazir->update($this->validasi($request));

        return redirect()->route('tazir.index')->with('sukses', 'Data ta\'zir berhasil diperbarui.');
    }

    public function destroy(Tazir $tazir)
    {
        $tazir->delete();

        return back()->with('sukses', 'Data ta\'zir berhasil dihapus.');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'id_pelanggaran' => ['required', 'integer', 'exists:pelanggaran,id_pelanggaran'],
            'jenis_tazir' => ['required', 'string', 'max:150'],
            'tanggal_pelaksanaan' => ['nullable', 'date'],
            'status_pelaksanaan' => ['required', 'in:menunggu,berjalan,selesai'],
            'keterangan' => ['nullable', 'string'],
        ], Validasi::pesan(), [
            'id_pelanggaran' => 'pelanggaran',
            'jenis_tazir' => 'jenis ta\'zir',
            'tanggal_pelaksanaan' => 'tanggal pelaksanaan',
            'status_pelaksanaan' => 'status pelaksanaan',
            'keterangan' => 'keterangan',
        ]);
    }
}
