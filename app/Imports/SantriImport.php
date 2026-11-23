<?php

namespace App\Imports;

use App\Models\Santri;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SantriImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function model(array $row): ?Model
    {
        // Lewati baris tanpa NIS atau nama santri
        if (empty($row['nis']) || empty($row['nama_santri'])) {
            return null;
        }

        $nis = trim((string) $row['nis']);

        // Lewati NIS yang sudah ada di database
        if (Santri::where('nis', $nis)->exists()) {
            return null;
        }

        // Konversi tanggal Excel menjadi format database
        $tanggalMasuk = $row['tanggal_masuk'] ?? null;

        if (!empty($tanggalMasuk)) {
            if (is_numeric($tanggalMasuk)) {
                $tanggalMasuk = ExcelDate::excelToDateTimeObject(
                    $tanggalMasuk
                )->format('Y-m-d');
            } else {
                $tanggalMasuk = date(
                    'Y-m-d',
                    strtotime($tanggalMasuk)
                );
            }
        }

        $santri = new Santri();

        $santri->forceFill([
            'nis'           => $nis,
            'nama_santri'   => trim($row['nama_santri']),
            'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
            'alamat'        => $row['alamat'] ?? null,
            'kamar'         => $row['kamar'] ?? null,
            'kelas'         => $row['kelas'] ?? null,
            'tanggal_masuk' => $tanggalMasuk,
            'status'        => $row['status'] ?? 'aktif',
        ]);

        return $santri;
    }
}
