@extends('layouts.app')

@section('title', 'Riwayat Lamaran')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Riwayat Lamaran</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Riwayat Lamaran</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Daftar Riwayat Lamaran</h4>
                    <div>
                        @if(!$jobApplications->isEmpty())
                            <form id="form-clear-all" action="{{ route('job-applications.clear-all') }}" method="POST"
                                class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm me-2" id="btn-clear-all">
                                    <i class="ri-delete-bin-5-line align-bottom me-1"></i> Bersihkan Semua Riwayat
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($jobApplications->isEmpty())
                        <div class="text-center py-5">
                            <i class="ri-file-list-3-line display-5 text-muted"></i>
                            <h5 class="mt-3">Belum ada riwayat lamaran</h5>
                            <p class="text-muted">Mulai kirim lamaran kerja pertama Anda.</p>
                            <a href="{{ route('job-applications.create') }}" class="btn btn-success">
                                <i class="ri-send-plane-line me-1"></i> Kirim Lamaran
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table id="datatable-list" class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Perusahaan</th>
                                        <th>Posisi</th>
                                        <th>Email Tujuan</th>
                                        <th>Status</th>
                                        <th>Detail / Error</th>
                                        <th>Tanggal Dibuat</th>
                                        <th class="text-center" style="width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($jobApplications as $index => $app)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $app->company_name }}</strong></td>
                                            <td>{{ $app->position }}</td>
                                            <td>{{ $app->recipient_email }}</td>
                                            <td>
                                                <span class="badge {{ $app->status_badge_class }}">
                                                    @if($app->status === 'sent')
                                                        <i class="ri-check-line me-1"></i>
                                                    @elseif($app->status === 'failed')
                                                        <i class="ri-error-warning-line me-1"></i>
                                                    @else
                                                        <i class="ri-time-line me-1"></i>
                                                    @endif
                                                    {{ ucfirst($app->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($app->status === 'failed')
                                                    <span class="text-danger small" title="{{ $app->error_message }}">
                                                        <i class="ri-error-warning-fill me-1"></i>
                                                        {{ \Illuminate\Support\Str::limit($app->error_message, 40) }}
                                                    </span>
                                                @elseif($app->status === 'pending' && $app->scheduled_at)
                                                    <span class="text-muted small">
                                                        <i class="ri-calendar-todo-line me-1"></i>
                                                        Kirim: {{ $app->scheduled_at->format('d M H:i') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $app->created_at->format('d M Y H:i') }}</td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center align-items-center gap-1">
                                                    @if($app->status === 'failed')
                                                        <form action="{{ route('job-applications.resend', $app->id) }}" method="POST"
                                                            class="d-inline form-resend">
                                                            @csrf
                                                            <button type="button" class="btn btn-sm btn-soft-warning btn-resend"
                                                                title="Kirim Ulang" data-company="{{ $app->company_name }}">
                                                                <i class="ri-restart-line"></i> Resend
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <form action="{{ route('job-applications.destroy', $app->id) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-soft-danger btn-delete"
                                                            data-name="Lamaran ke {{ $app->company_name }}">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#datatable-list').DataTable({
                responsive: true,
                language: {
                    search: "Cari Data:",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Lanjut",
                        previous: "Kembali"
                    }
                },
                columnDefs: [
                    {
                        targets: -1,
                        className: 'text-center align-middle',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // Handler untuk Bersihkan Semua Riwayat
            $('#btn-clear-all').on('click', function (e) {
                e.preventDefault();
                const form = $('#form-clear-all');

                Swal.fire({
                    title: 'Kosongkan Seluruh Riwayat?',
                    text: 'Semua riwayat pengiriman dan draft lamaran Anda akan dihapus permanen!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Bersihkan Semua!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger w-xs me-2',
                        cancelButton: 'btn btn-light w-xs'
                    },
                    buttonsStyling: false,
                    showCloseButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Handler untuk Kirim Ulang (Resend)
            $(document).on('click', '.btn-resend', function (e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const company = $(this).data('company');

                Swal.fire({
                    title: 'Kirim Ulang Email?',
                    text: `Kirim ulang lamaran kerja ke ${company}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Kirim Ulang',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-warning w-xs me-2',
                        cancelButton: 'btn btn-light w-xs'
                    },
                    buttonsStyling: false,
                    showCloseButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Mengirim Ulang...',
                            text: 'Sedang menghubungkan ke server SMTP...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush