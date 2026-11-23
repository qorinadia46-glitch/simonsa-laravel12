@extends('layouts.app')

@section('judul', 'Import Kategori Pelanggaran')
@section('subjudul', 'Import data kategori pelanggaran melalui Excel')

@section('content')
<div class="kartu kartu-isi">
    <h5>Import Kategori Pelanggaran Excel</h5>

    <p>
        Pilih file Excel yang berisi data kategori
        pelanggaran untuk dimasukkan ke database SIMONSA.
    </p>

    <div class="alert alert-info">
        Format kolom Excel:
        kode_pelanggaran, nama_pelanggaran,
        deskripsi, poin, status.
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('kategori.import.proses') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label class="form-label">Pilih File Excel</label>
            <input type="file"
                   name="file"
                   class="form-control"
                   accept=".xlsx,.xls,.csv"
                   required>
        </div>

        <button type="submit" class="btn-hijau">
            <i class="bi bi-upload"></i> Import Sekarang
        </button>

        <a href="{{ route('kategori.index') }}" class="btn-sm-aksi">
            Kembali
        </a>
    </form>
</div>
@endsection