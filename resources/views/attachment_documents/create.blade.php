@extends('layouts.app')

@section('title', 'Upload Dokumen Lampiran')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Upload Dokumen Lampiran</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attachment-documents.index') }}">Dokumen Lampiran</a></li>
                    <li class="breadcrumb-item active">Upload</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Form Upload Dokumen</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('attachment-documents.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Dokumen</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}"
                            placeholder="Contoh: CV - Backend Laravel Developer" required>
                        <small class="text-muted">Berikan nama yang mudah dikenali</small>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">File PDF</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".pdf" required>
                        <small class="text-muted">
                            Format: PDF | Maksimal ukuran: 5MB
                        </small>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('attachment-documents.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-upload-cloud-line me-1"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Petunjuk</h4>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li class="mb-2">File harus berekstensi <strong>.pdf</strong></li>
                    <li class="mb-2">Ukuran maksimal file adalah <strong>5MB</strong></li>
                    <li class="mb-2">Pastikan file PDF dapat dibaca dengan baik</li>
                    <li class="mb-0">Dokumen ini akan dilampirkan saat mengirim email lamaran</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
