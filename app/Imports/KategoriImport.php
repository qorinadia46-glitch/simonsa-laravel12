<?php

namespace App\Imports;

use App\Models\KategoriPelanggaran;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class KategoriImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function model(array $row): ?Model
    {
        if (
            empty($row['kode_pelanggaran']) ||
            empty($row['nama_pelanggaran']) ||
            !isset($row['poin']) ||
            $row['poin'] === ''
        ) {
            return null;
        }

        $kode = strtoupper(trim((string) $row['kode_pelanggaran']));

        // Cek agar kode pelanggaran tidak duplikat
        if (KategoriPelanggaran::where('kode_pelanggaran', $kode)->exists()) {
            return null;
        }

        $poin = filter_var($row['poin'], FILTER_VALIDATE_INT);

        if ($poin === false || $poin < 0) {
            throw new \RuntimeException(
                "Poin untuk kode $kode harus berupa bilangan bulat tidak negatif."
            );
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'aktif')));

        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            throw new \RuntimeException(
                "Status untuk kode $kode harus aktif atau nonaktif."
            );
        }

        return new KategoriPelanggaran([
            'kode_pelanggaran' => $kode,
            'nama_pelanggaran' => trim($row['nama_pelanggaran']),
            'deskripsi' => $row['deskripsi'] ?? null,
            'poin' => $poin,
            'status' => $status,
        ]);
    }
}
