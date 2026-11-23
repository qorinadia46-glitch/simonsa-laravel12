<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TabelLaporanSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private string $nama,
        private array $kolom,
        private array $baris
    ) {
    }

    public function array(): array
    {
        return $this->baris;
    }

    public function headings(): array
    {
        return $this->kolom;
    }

    public function title(): string
    {
        return $this->nama;
    }
}
