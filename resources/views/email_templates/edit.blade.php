@extends('layouts.app')

@section('title', 'Edit Template Email')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Template Email</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('email-templates.index') }}">Template Email</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Form Edit Template Email</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('email-templates.update', $emailTemplate) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Template</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $emailTemplate->title) }}"
                            placeholder="Contoh: Template Backend Developer" required>
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">Isi Email</label>
                        <textarea class="form-control" id="body" name="body" rows="10" required>{{ old('body', $emailTemplate->body) }}</textarea>
                        <small class="text-muted">
                            Placeholder: <code>{nama_perusahaan}</code>, <code>{posisi_pekerjaan}</code>
                        </small>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('email-templates.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-save-line me-1"></i> Update Template
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
                <h6>Placeholder yang tersedia:</h6>
                <ul class="mb-3">
                    <li><code>{nama_perusahaan}</code> - Akan digantikan dengan nama perusahaan</li>
                    <li><code>{posisi_pekerjaan}</code> - Akan digantikan dengan posisi yang dilamar</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
