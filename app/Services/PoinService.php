<?php

namespace App\Services;

use App\Models\KategoriPelanggaran;
use App\Models\Notifikasi;
use App\Models\Pelanggaran;
use App\Models\PengaturanSistem;
use App\Models\Pengguna;
use App\Models\Santri;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PoinService
{
    protected static ?array $batasCache = null;

    public static function lupakanBatas(): void
    {
        self::$batasCache = null;
    }

    public static function batas(): array
    {
        if (self::$batasCache !== null) {
            return self::$batasCache;
        }

        return self::$batasCache = [
            'sp1' => (int) PengaturanSistem::ambil('batas_sp1', '25'),
            'sp2' => (int) PengaturanSistem::ambil('batas_sp2', '50'),
            'sp3' => (int) PengaturanSistem::ambil('batas_sp3', '75'),
        ];
    }

    public static function tingkat(int $poin): ?string
    {
        $batas = self::batas();
        if ($batas['sp3'] > 0 && $poin >= $batas['sp3']) {
            return 'SP3';
        }
        if ($batas['sp2'] > 0 && $poin >= $batas['sp2']) {
            return 'SP2';
        }
        if ($batas['sp1'] > 0 && $poin >= $batas['sp1']) {
            return 'SP1';
        }

        return null;
    }

    public static function progres(int $poin): array
    {
        $batas = self::batas();
        if ($poin >= $batas['sp3']) {
            return ['label' => 'Sudah mencapai SP3', 'persen' => 100, 'target' => $batas['sp3']];
        }
        if ($poin >= $batas['sp2']) {
            return ['label' => 'Menuju SP3', 'persen' => self::persen($poin, $batas['sp3']), 'target' => $batas['sp3']];
        }
        if ($poin >= $batas['sp1']) {
            return ['label' => 'Menuju SP2', 'persen' => self::persen($poin, $batas['sp2']), 'target' => $batas['sp2']];
        }

        return ['label' => 'Menuju SP1', 'persen' => self::persen($poin, $batas['sp1']), 'target' => $batas['sp1']];
    }

    public static function total(int $idSantri): int
    {
        return (int) Pelanggaran::where('id_santri', $idSantri)->sum('poin_pelanggaran');
    }

    public static function catat(array $data, Pengguna $petugas, $waktu = null): array
    {
        return DB::transaction(function () use ($data, $petugas, $waktu) {
            $kategori = KategoriPelanggaran::where('status', 'aktif')->findOrFail($data['id_kategori']);
            $santri = Santri::findOrFail($data['id_santri']);
            $sebelum = self::total($santri->id_santri);
            $tingkatSebelum = self::tingkat($sebelum);
            $saat = $waktu ? Carbon::parse($waktu) : now();

            $pelanggaran = Pelanggaran::create([
                'id_santri' => $santri->id_santri,
                'id_kategori' => $kategori->id_kategori,
                'id_user' => $petugas->id_user,
                'tanggal_pelanggaran' => $data['tanggal_pelanggaran'],
                'kronologi' => $data['kronologi'],
                'poin_pelanggaran' => $kategori->poin,
                'tindak_lanjut' => $data['tindak_lanjut'] ?? null,
                'bukti_foto' => $data['bukti_foto'] ?? null,
                'created_at' => $saat,
            ]);

            $sesudah = $sebelum + $kategori->poin;
            $tingkatSesudah = self::tingkat($sesudah);
            $naik = self::peringkat($tingkatSesudah) > self::peringkat($tingkatSebelum);
            if ($naik && $tingkatSesudah) {
                self::kirimNotifikasi($santri, $sesudah, $tingkatSesudah, $saat);
            }

            return [
                'pelanggaran' => $pelanggaran,
                'total' => $sesudah,
                'tingkat' => $tingkatSesudah,
                'naik' => $naik,
            ];
        });
    }

    public static function perbarui(Pelanggaran $pelanggaran, array $data): array
    {
        return DB::transaction(function () use ($pelanggaran, $data) {
            $kategori = KategoriPelanggaran::findOrFail($data['id_kategori']);
            $santri = Santri::findOrFail($data['id_santri']);
            $totalSebelum = self::total($santri->id_santri);
            if ((int) $pelanggaran->id_santri === (int) $santri->id_santri) {
                $totalSebelum -= (int) $pelanggaran->poin_pelanggaran;
            }
            $tingkatSebelum = self::tingkat($totalSebelum);

            $pelanggaran->fill([
                'id_santri' => $santri->id_santri,
                'id_kategori' => $kategori->id_kategori,
                'tanggal_pelanggaran' => $data['tanggal_pelanggaran'],
                'kronologi' => $data['kronologi'],
                'poin_pelanggaran' => $kategori->poin,
                'tindak_lanjut' => $data['tindak_lanjut'] ?? null,
                'bukti_foto' => $data['bukti_foto'] ?? $pelanggaran->bukti_foto,
            ])->save();

            $sesudah = $totalSebelum + $kategori->poin;
            $tingkatSesudah = self::tingkat($sesudah);
            $naik = self::peringkat($tingkatSesudah) > self::peringkat($tingkatSebelum);
            if ($naik && $tingkatSesudah) {
                self::kirimNotifikasi($santri, $sesudah, $tingkatSesudah, now());
            }

            return [
                'pelanggaran' => $pelanggaran,
                'total' => $sesudah,
                'tingkat' => $tingkatSesudah,
                'naik' => $naik,
            ];
        });
    }

    public static function kirimNotifikasi(Santri $santri, int $poin, string $tingkat, $waktu = null): void
    {
        $saat = $waktu ? Carbon::parse($waktu) : now();
        $penerima = Pengguna::whereIn('role', ['admin', 'keamanan'])->where('status', 'aktif')->get();

        foreach ($penerima as $user) {
            Notifikasi::create([
                'id_santri' => $santri->id_santri,
                'id_user' => $user->id_user,
                'judul' => 'Batas poin '.$tingkat.' tercapai',
                'pesan' => 'Santri '.$santri->nama_santri.' (NIS '.$santri->nis.') mencapai akumulasi '.$poin.' poin dan masuk tingkat '.$tingkat.'. Segera terbitkan surat peringatan. Keputusan sanksi tetap dilakukan di luar sistem.',
                'jenis_notifikasi' => 'batas_poin',
                'status_baca' => 'belum',
                'created_at' => $saat,
            ]);
        }
    }

    private static function peringkat(?string $tingkat): int
    {
        return match ($tingkat) {
            'SP1' => 1,
            'SP2' => 2,
            'SP3' => 3,
            default => 0,
        };
    }

    private static function persen(int $poin, int $target): int
    {
        if ($target <= 0) {
            return 0;
        }

        return min(100, (int) round($poin / $target * 100));
    }
}
