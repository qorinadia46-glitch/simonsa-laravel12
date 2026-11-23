@extends('layouts.app')

@section('judul', 'Import Data Santri')
@section('subjudul', 'Import banyak data santri melalui Excel')

@section('content')

<div class="kartu kartu-isi">

    <h5>Import Data Santri Excel</h5>

    <p>
        Pilih file Excel yang berisi data santri
        untuk dimasukkan ke dalam SIMONSA.
    </p>

    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <strong>Belum punya format Excel?</strong>
            <div>Unduh template, isi data mulai baris kedua tanpa mengubah judul kolom.</div>
            <small>NIS, nama santri, dan jenis kelamin wajib diisi (Laki-laki/Perempuan).</small>
        </div>
        <a href="{{ route('santri.import.template', [], false) }}" class="btn btn-outline-success">
            <i class="bi bi-download"></i>
            Unduh Template Excel
        </a>
    </div>

    <form action="{{ route('santri.import.proses') }}"
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
            <i class="bi bi-upload"></i>
            Import Sekarang
        </button>

        <a href="{{ route('santri.index') }}"
           class="btn-sm-aksi">
            Kembali
        </a>

    </form>

</div>

@endsection