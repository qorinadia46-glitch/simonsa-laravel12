<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KategoriImport;
use App\Exports\TemplateKategoriExport;
use Throwable;

class ImportKategoriController extends Controller
{
    public function form()
    {
        return view('kategori.import');
    }

    public function template()
    {
        return Excel::download(
            new TemplateKategoriExport(),
            'template-kategori-pelanggaran.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Silakan pilih file Excel.',
            'file.mimes' => 'File harus berformat XLSX, XLS, atau CSV.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            Excel::import(new KategoriImport(), $request->file('file'));

            return redirect()->route('kategori.index')->with(
                'sukses',
                'Import kategori pelanggaran berhasil!'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'file' => 'Import gagal: ' . $e->getMessage()
            ]);
        }
    }
}
