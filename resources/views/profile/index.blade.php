@extends('layouts.app')

@section('title', 'Profil Pengguna')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Profil Pengguna</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informasi Profil Card -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Informasi Akun</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Username</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <div class="d-flex align-items-center">
                                <input type="text" class="form-control me-2"
                                    value="{{ $user->role === 'admin' ? 'Admin' : 'User' }}" disabled>
                                <span
                                    class="badge {{ $user->role === 'admin' ? 'bg-danger' : 'bg-success' }} text-uppercase">
                                    {{ $user->role }}
                                </span>
                            </div>
                            <small class="text-muted">Hak akses akun tidak dapat diubah.</small>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="ri-save-line me-1"></i> Perbarui Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Keamanan / Ganti Password Card -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Keamanan & Ubah Password</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                id="current_password" name="current_password" placeholder="Masukkan password saat ini"
                                required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password" placeholder="Minimal 8 karakter" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation" placeholder="Ulangi password baru" required>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-danger">
                                <i class="ri-key-line me-1"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- API Tokens Management Card -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header align-items-center d-flex justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Manajemen REST API Token (Sanctum)</h4>
                        <p class="text-muted small mb-0 mt-1">Gunakan token Bearer untuk mengirim lamaran otomatis melalui script atau aplikasi pihak ketiga.</p>
                    </div>
                    <div>
                        <a href="{{ route('api.docs') }}" target="_blank" class="btn btn-soft-info btn-sm me-2">
                            <i class="ri-book-open-line me-1"></i> Dokumentasi API (Scalar)
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateApiToken">
                            <i class="ri-add-line me-1"></i> Buat Token Baru
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('new_api_token'))
                        <div class="alert alert-success border-0 shadow-sm mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="ri-checkbox-circle-fill fs-20 text-success me-2"></i>
                                <strong class="fs-15">Token Berhasil Dibuat!</strong>
                            </div>
                            <p class="mb-2 text-muted small">
                                Salin token di bawah ini sekarang. <strong>Token ini tidak akan pernah ditampilkan lagi</strong> demi alasan keamanan.
                            </p>
                            <div class="input-group">
                                <input type="text" id="plainTextTokenInput" class="form-control font-monospace bg-white"
                                    value="{{ session('new_api_token') }}" readonly>
                                <button class="btn btn-success" type="button" id="btnCopyToken">
                                    <i class="ri-file-copy-line me-1"></i> Salin Token
                                </button>
                            </div>
                        </div>
                    @endif

                    @if($tokens->isEmpty())
                        <div class="text-center py-4">
                            <i class="ri-key-2-line display-6 text-muted"></i>
                            <h6 class="mt-3">Belum ada API Token yang aktif</h6>
                            <p class="text-muted small">Buat token untuk mulai mengintegrasikan ApplyGo dengan workflow otomasi Anda.</p>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateApiToken">
                                <i class="ri-add-line me-1"></i> Buat Token Sekarang
                            </button>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Token</th>
                                        <th>Kemampuan (Abilities)</th>
                                        <th>Terakhir Digunakan</th>
                                        <th>Dibuat Pada</th>
                                        <th class="text-center" style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tokens as $token)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-2">
                                                        <span class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                            <i class="ri-terminal-box-line"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <strong class="d-block">{{ $token->name }}</strong>
                                                        <small class="text-muted">ID: {{ $token->id }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">Semua Akses (*)</span>
                                            </td>
                                            <td>
                                                {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Belum pernah digunakan' }}
                                            </td>
                                            <td>
                                                {{ $token->created_at->format('d M Y H:i') }}
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('api-tokens.destroy', $token->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-soft-danger btn-delete"
                                                        data-name="Token '{{ $token->name }}'">
                                                        <i class="ri-delete-bin-line me-1"></i> Cabut
                                                    </button>
                                                </form>
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

    <!-- Modal Buat Token Baru -->
    <div class="modal fade" id="modalCreateApiToken" tabindex="-1" aria-labelledby="modalCreateApiTokenLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateApiTokenLabel">Buat API Token Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('api-tokens.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="token_name" class="form-label">Nama Token <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="token_name" name="token_name"
                                placeholder="Misal: Script Otomasi Python, Browser Extension" required>
                            <small class="text-muted">Beri nama unik agar mudah mengenali penggunaan token ini.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-key-line me-1"></i> Generate Token
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#btnCopyToken').on('click', function() {
            var copyText = document.getElementById("plainTextTokenInput");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Tersalin!',
                    text: 'API Token berhasil disalin ke clipboard.',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        });
    });
</script>
@endpush