@extends('layouts.app')

@section('title', 'Upload Template Dokumen')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Upload Template Dokumen</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('document-templates.index') }}">Template Dokumen</a></li>
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
                <h4 class="card-title mb-0">Form Upload Template</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('document-templates.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Template</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}"
                            placeholder="Contoh: Template Cover Letter 2026" required>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">File Word (.docx)</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".docx" required>
                        <small class="text-muted">
                            Format: DOCX | Maksimal ukuran: 10MB
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        <strong>Petunjuk Placeholder:</strong> Di dalam file Word, gunakan placeholder berikut yang akan diganti otomatis:
                        <ul class="mb-0 mt-2">
                            <li><code>${NAMA_PERUSAHAAN}</code> - Nama perusahaan</li>
                            <li><code>${POSISI}</code> - Posisi yang dilamar</li>
                        </ul>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('document-templates.index') }}" class="btn btn-secondary me-2">Batal</a>
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
                    <li class="mb-2">File harus berekstensi <strong>.docx</strong></li>
                    <li class="mb-2">Ukuran maksimal file adalah <strong>10MB</strong></li>
                    <li class="mb-2">Gunakan placeholder di dalam dokumen:
                        <ul>
                            <li><code>${NAMA_PERUSAHAAN}</code></li>
                            <li><code>${POSISI}</code></li>
                        </ul>
                    </li>
                    <li class="mb-0">Template akan diproses saat generate cover letter</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
