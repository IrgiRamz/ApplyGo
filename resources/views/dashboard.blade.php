@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Dashboard</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Beranda</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Total Lamaran</p>
                        <h4 class="fs-22 fw-semibold mb-0">{{ $stats['total'] }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-3">
                            <i class="ri-file-list-3-line text-primary"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Terkirim</p>
                        <h4 class="fs-22 fw-semibold mb-0">{{ $stats['sent'] }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-3">
                            <i class="ri-send-plane-line text-success"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Tertunda</p>
                        <h4 class="fs-22 fw-semibold mb-0">{{ $stats['pending'] }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-3">
                            <i class="ri-time-line text-warning"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0">Gagal</p>
                        <h4 class="fs-22 fw-semibold mb-0">{{ $stats['failed'] }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded fs-3">
                            <i class="ri-error-warning-line text-danger"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Recent Applications -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Lamaran Terbaru</h4>
                <a href="{{ route('job-applications.index') }}" class="btn btn-sm btn-success">Lihat Semua</a>
            </div>
            <div class="card-body">
                @if($recentApplications->isEmpty())
                    <div class="text-center py-5">
                        <i class="ri-inbox-line display-5 text-muted"></i>
                        <h5 class="mt-3">Belum ada lamaran</h5>
                        <p class="text-muted">Mulai kirim lamaran kerja pertama Anda.</p>
                        <a href="{{ route('job-applications.create') }}" class="btn btn-success">
                            <i class="ri-send-plane-line me-1"></i> Kirim Lamaran
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Perusahaan</th>
                                    <th>Posisi</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentApplications as $app)
                                <tr>
                                    <td>{{ $app->company_name }}</td>
                                    <td>{{ $app->position }}</td>
                                    <td>
                                        <span class="badge {{ $app->status_badge_class }}">{{ ucfirst($app->status) }}</span>
                                    </td>
                                    <td>{{ $app->created_at->format('d M Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Aksi Cepat</h4>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('job-applications.create') }}" class="btn btn-success w-100">
                        <i class="ri-send-plane-line me-2"></i> Kirim Lamaran Baru
                    </a>
                    <a href="{{ route('cover-letter.form') }}" class="btn btn-primary w-100">
                        <i class="ri-file-list-3-line me-2"></i> Generate Cover Letter
                    </a>
                    <a href="{{ route('email-templates.create') }}" class="btn btn-info w-100">
                        <i class="ri-file-text-line me-2"></i> Buat Template Email
                    </a>
                    <a href="{{ route('attachment-documents.create') }}" class="btn btn-warning w-100">
                        <i class="ri-file-paper-2-line me-2"></i> Upload CV PDF
                    </a>
                </div>
            </div>
        </div>

        @if(!auth()->user()->hasEmailSetting())
            <div class="card border-warning border">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ri-error-warning-line text-warning fs-2"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fs-14">SMTP Belum Diatur</h5>
                            <p class="text-muted mb-0">Konfigurasi SMTP untuk dapat mengirim email lamaran.</p>
                            <a href="{{ route('email-settings.edit') }}" class="btn btn-sm btn-warning mt-2">
                                Atur Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
