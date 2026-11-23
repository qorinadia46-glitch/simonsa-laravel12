<?php

namespace App\Exports;

use App\Services\RekapService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DetailPelanggaranSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithColumnWidths
{
    public function __construct(
        private array $filter,
        private RekapService $rekap
    ) {
    }

    public function query(): Builder
    {
        return $this->rekap->query($this->filter)
            ->with(['santri', 'kategori', 'pengguna'])
            ->orderByDesc('tanggal_pelanggaran')
            ->orderByDesc('id_pelanggaran');
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'NIS',
            'Nama Santri',
            'Kelas',
            'Kode Kategori',
            'Kategori Pelanggaran',
            'Poin',
            'Kronologi',
            'Tindak Lanjut',
            'Petugas',
        ];
    }

    public function map($pelanggaran): array
    {
        return [
            $pelanggaran->tanggal_pelanggaran->format('Y-m-d'),
            $pelanggaran->santri->nis,
            $pelanggaran->santri->nama_santri,
            $pelanggaran->santri->kelas,
            $pelanggaran->kategori->kode_pelanggaran,
            $pelanggaran->kategori->nama_pelanggaran,
            $pelanggaran->poin_pelanggaran,
            $pelanggaran->kronologi,
            $pelanggaran->tindak_lanjut,
            $pelanggaran->pengguna?->nama_user ?? '-',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 16,
            'C' => 30,
            'D' => 14,
            'E' => 18,
            'F' => 30,
            'G' => 10,
            'H' => 55,
            'I' => 40,
            'J' => 26,
        ];
    }

    public function title(): string
    {
        return 'Detail Pelanggaran';
    }
}
