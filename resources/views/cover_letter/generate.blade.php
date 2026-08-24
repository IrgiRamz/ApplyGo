@extends('layouts.app')

@section('title', 'Generator Dokumen')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Generator Dokumen</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Generator Dokumen</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if($documentTemplates->isEmpty())
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="ri-file-word-2-line display-5 text-muted"></i>
                        <h5 class="mt-3">Belum ada template dokumen</h5>
                        <p class="text-muted">Upload template Word (.docx) terlebih dahulu untuk dapat generate dokumen.</p>
                        <a href="{{ route('document-templates.create') }}" class="btn btn-success">
                            <i class="ri-upload-cloud-line me-1"></i> Upload Template
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Form Generator Dokumen</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('cover-letter.generate') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="document_template_id" class="form-label">Pilih Template</label>
                                <select class="form-select" id="document_template_id" name="document_template_id" required>
                                    <option value="">-- Pilih Template --</option>
                                    @foreach($documentTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_name" class="form-label">Nama Perusahaan</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name"
                                            value="{{ old('company_name') }}" placeholder="PT. Contoh Indonesia" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="position" class="form-label">Posisi yang Dilamar</label>
                                        <input type="text" class="form-control" id="position" name="position"
                                            value="{{ old('position') }}" placeholder="Backend Developer" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Format Output</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="format_docx" value="docx"
                                            checked>
                                        <label class="form-check-label" for="format_docx">
                                            <i class="ri-file-word-2-line text-primary me-1"></i> Microsoft Word (.docx)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="format_pdf" value="pdf">
                                        <label class="form-check-label" for="format_pdf">
                                            <i class="ri-file-pdf-line text-danger me-1"></i> PDF
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-download-cloud-line me-1"></i> Generate & Download
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
                        <h6>Placeholder yang Diganti:</h6>
                        <ul class="mb-3">
                            <li><code>${NAMA_PERUSAHAAN}</code> → Nama perusahaan yang Anda input</li>
                            <li><code>${POSISI}</code> → Posisi yang Anda input</li>
                        </ul>

                        <h6>Format Output:</h6>
                        <ul class="mb-0">
                            <li><strong>DOCX:</strong> File Microsoft Word yang bisa diedit</li>
                            <li><strong>PDF:</strong> File siap cetak</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection