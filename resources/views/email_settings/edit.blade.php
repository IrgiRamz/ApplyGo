@extends('layouts.app')

@section('title', 'Pengaturan SMTP')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Pengaturan SMTP</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Pengaturan SMTP</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Konfigurasi SMTP Pribadi</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="ri-information-line me-2"></i>
                    <strong>Informasi:</strong> Gunakan App Password untuk Gmail. Aktifkan "Less secure app access" atau gunakan App Password di akun Google Anda.
                </div>

                <form action="{{ route('email-settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_host" class="form-label">Mail Host</label>
                                <input type="text" class="form-control" id="mail_host" name="mail_host"
                                    value="{{ old('mail_host', $emailSetting->mail_host ?? 'smtp.gmail.com') }}"
                                    placeholder="smtp.gmail.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_port" class="form-label">Mail Port</label>
                                <input type="number" class="form-control" id="mail_port" name="mail_port"
                                    value="{{ old('mail_port', $emailSetting->mail_port ?? 587) }}"
                                    placeholder="587" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_username" class="form-label">Email Pengirim</label>
                                <input type="email" class="form-control" id="mail_username" name="mail_username"
                                    value="{{ old('mail_username', $emailSetting->mail_username ?? '') }}"
                                    placeholder="email@gmail.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_password" class="form-label">App Password</label>
                                <input type="password" class="form-control" id="mail_password" name="mail_password"
                                    placeholder="{{ $emailSetting ? 'Kosongkan jika tidak diubah' : 'Masukkan password' }}"
                                    {{ $emailSetting ? '' : 'required' }}>
                                <small class="text-muted">Gunakan App Password untuk Gmail</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_encryption" class="form-label">Encryption</label>
                                <select class="form-select" id="mail_encryption" name="mail_encryption" required>
                                    <option value="tls" {{ ($emailSetting->mail_encryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ $emailSetting?->mail_encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sender_name" class="form-label">Nama Pengirim</label>
                                <input type="text" class="form-control" id="sender_name" name="sender_name"
                                    value="{{ old('sender_name', $emailSetting->sender_name ?? auth()->user()->name) }}"
                                    placeholder="John Doe, S.Kom" required>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" id="btn-test-smtp" class="btn btn-info me-2">
                            <i class="ri-mail-check-line me-1"></i> Test Koneksi SMTP
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-save-line me-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            $('#btn-test-smtp').on('click', function(e) {
                e.preventDefault();

                // Ambil data form
                var formData = {
                    _token: $('input[name="_token"]').val(),
                    mail_host: $('#mail_host').val(),
                    mail_port: $('#mail_port').val(),
                    mail_username: $('#mail_username').val(),
                    mail_password: $('#mail_password').val(),
                    mail_encryption: $('#mail_encryption').val(),
                    sender_name: $('#sender_name').val()
                };

                // Tampilkan loading SweetAlert
                Swal.fire({
                    title: 'Menguji Koneksi SMTP...',
                    text: 'Mohon tunggu sebentar, sistem sedang melakukan handshake ke server email.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Kirim request AJAX ke backend
                $.ajax({
                    url: "{{ route('email-settings.test') }}",
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Koneksi Sukses!',
                            text: response.message,
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-primary w-xs'
                            },
                            buttonsStyling: false
                        });
                    },
                    error: function(xhr) {
                        var errorMsg = "Gagal terhubung ke server SMTP.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Koneksi Gagal!',
                            text: errorMsg,
                            confirmButtonText: 'Tutup',
                            customClass: {
                                confirmButton: 'btn btn-primary w-xs'
                            },
                            buttonsStyling: false
                        });
                    }
                });
            });
        });
    </script>
    @endpush

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Petunjuk Gmail</h4>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li class="mb-2">Buka akun Google Anda</li>
                    <li class="mb-2">Pilih "Security" di menu pengaturan</li>
                    <li class="mb-2">Aktifkan "2-Step Verification"</li>
                    <li class="mb-2">Pilih "App passwords" di bagian Sign in</li>
                    <li class="mb-2">Buat App Password baru untuk "Mail"</li>
                    <li class="mb-2">Salin password yang dihasilkan</li>
                    <li class="mb-0">Tempelkan App Password di form</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
