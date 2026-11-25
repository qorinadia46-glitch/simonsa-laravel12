<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateKategoriExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'kode_pelanggaran',
            'nama_pelanggaran',
            'deskripsi',
            'poin',
            'status',
        ];
    }
}
