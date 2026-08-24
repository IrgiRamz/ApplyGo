@extends('layouts.app')

@section('title', 'Template Email')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Template Email</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Template Email</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Template Email</h4>
                <a href="{{ route('email-templates.create') }}" class="btn btn-success">
                    <i class="ri-add-line me-1"></i> Tambah Template
                </a>
            </div>
            <div class="card-body">
                @if($emailTemplates->isEmpty())
                    <div class="text-center py-5">
                        <i class="ri-file-text-line display-5 text-muted"></i>
                        <h5 class="mt-3">Belum ada template email</h5>
                        <p class="text-muted">Buat template email untuk mempercepat pengiriman lamaran.</p>
                        <a href="{{ route('email-templates.create') }}" class="btn btn-success">
                            <i class="ri-add-line me-1"></i> Buat Template
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table id="datatable-list" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Isi Template</th>
                                    <th class="text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emailTemplates as $index => $template)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $template->title }}</strong></td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($template->body, 100) }}</small>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <a href="{{ route('email-templates.edit', $template) }}" class="btn btn-sm btn-soft-primary">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            <form action="{{ route('email-templates.destroy', $template) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-soft-danger btn-delete" data-name="{{ $template->title }}">
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
    $(document).ready(function() {
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
    });
</script>
@endpush
