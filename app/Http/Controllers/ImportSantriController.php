<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SantriImport;
use App\Exports\TemplateSantriExport;
use Throwable;

class ImportSantriController extends Controller
{
    public function form()
    {
        return view('santri.import');
    }

    public function template()
    {
        return Excel::download(
            new TemplateSantriExport(),
            'template-data-santri.xlsx'
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
            Excel::import(new SantriImport(), $request->file('file'));

            return redirect()->back()->with(
                'success',
                'Proses import data santri berhasil!'
            );
        } catch (Throwable $e) {
            return redirect()->back()->withErrors([
                'file' => 'Import gagal: ' . $e->getMessage()
            ]);
        }
    }
}
