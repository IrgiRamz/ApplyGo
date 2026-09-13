@extends('layouts.app')

@section('title', 'Portal Karir Perusahaan')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Portal Karir Perusahaan</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">URL Karir Perusahaan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Daftar Website & Portal Karir</h4>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal"
                        data-bs-target="#modalAddCompanyUrl">
                        <i class="ri-add-line me-1"></i> Tambah URL Baru
                    </button>
                </div>
                <div class="card-body">
                    @if($companyUrls->isEmpty())
                        <div class="text-center py-5">
                            <i class="ri-links-line display-5 text-muted"></i>
                            <h5 class="mt-3">Belum ada link portal karir</h5>
                            <p class="text-muted">Simpan link website karir perusahaan untuk mengakses halaman pendaftaran
                                lowongan dengan cepat.</p>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#modalAddCompanyUrl">
                                <i class="ri-add-line me-1"></i> Tambah URL Baru
                            </button>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table id="datatable-list" class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Nama Perusahaan</th>
                                        <th>URL Karir / Pendaftaran</th>
                                        <th class="text-center" style="width: 130px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($companyUrls as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $item->company_name }}</strong></td>
                                            <td>
                                                <a href="{{ $item->career_url }}" target="_blank" class="btn btn-sm btn-soft-info">
                                                    {{ \Illuminate\Support\Str::limit($item->career_url, 40) }}
                                                </a>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-soft-warning btn-edit"
                                                        data-id="{{ $item->id }}" data-name="{{ $item->company_name }}"
                                                        data-url="{{ $item->career_url }}" data-bs-toggle="modal"
                                                        data-bs-target="#modalEditCompanyUrl" title="Edit">
                                                        <i class="ri-edit-line"></i>
                                                    </button>
                                                    <form action="{{ route('company-urls.destroy', $item->id) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-soft-danger btn-delete"
                                                            data-name="{{ $item->company_name }}" title="Hapus">
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

    <!-- Modal Tambah URL Karir -->
    <div class="modal fade" id="modalAddCompanyUrl" tabindex="-1" aria-labelledby="modalAddCompanyUrlLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddCompanyUrlLabel">Tambah Link Portal Karir Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('company-urls.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Nama Perusahaan <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name"
                                placeholder="Contoh: PT. Tokopedia / GoTo" required>
                        </div>
                        <div class="mb-3">
                            <label for="career_url" class="form-label">URL Website Karir / Pendaftaran <span
                                    class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="career_url" name="career_url"
                                placeholder="https://karir.company.com/jobs" required>
                            <small class="text-muted">Pastikan menggunakan format lengkap diawali <code>http://</code> atau
                                <code>https://</code>.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-save-line me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit URL Karir -->
    <div class="modal fade" id="modalEditCompanyUrl" tabindex="-1" aria-labelledby="modalEditCompanyUrlLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditCompanyUrlLabel">Edit Link Portal Karir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditCompanyUrl" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_company_name" class="form-label">Nama Perusahaan <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_company_name" name="company_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_career_url" class="form-label">URL Website Karir / Pendaftaran <span
                                    class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="edit_career_url" name="career_url" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Perbarui
                        </button>
                    </div>
                </form>
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

            // Populate Modal Edit
            $(document).on('click', '.btn-edit', function () {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const url = $(this).data('url');

                $('#formEditCompanyUrl').attr('action', `{{ url('company-urls') }}/${id}`);
                $('#edit_company_name').val(name);
                $('#edit_career_url').val(url);
            });
        });
    </script>
@endpush