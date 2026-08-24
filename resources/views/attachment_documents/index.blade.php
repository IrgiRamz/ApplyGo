@extends('layouts.app')

@section('title', 'Dokumen Lampiran')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Dokumen Lampiran (CV PDF)</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Dokumen Lampiran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Dokumen Lampiran</h4>
                <a href="{{ route('attachment-documents.create') }}" class="btn btn-success">
                    <i class="ri-add-line me-1"></i> Upload Dokumen
                </a>
            </div>
            <div class="card-body">
                @if($attachmentDocuments->isEmpty())
                    <div class="text-center py-5">
                        <i class="ri-file-paper-2-line display-5 text-muted"></i>
                        <h5 class="mt-3">Belum ada dokumen lampiran</h5>
                        <p class="text-muted">Upload CV PDF untuk dilampirkan saat mengirim lamaran.</p>
                        <a href="{{ route('attachment-documents.create') }}" class="btn btn-success">
                            <i class="ri-upload-cloud-line me-1"></i> Upload CV
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table id="datatable-list" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Nama File</th>
                                    <th>Tanggal Upload</th>
                                    <th class="text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attachmentDocuments as $index => $doc)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $doc->title }}</strong></td>
                                    <td>
                                        <i class="ri-file-pdf-line text-danger me-1"></i>
                                        {{ $doc->file_name }}
                                    </td>
                                    <td>{{ $doc->created_at->format('d M Y') }}</td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <form action="{{ route('attachment-documents.destroy', $doc) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-soft-danger btn-delete" data-name="{{ $doc->title }}">
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
