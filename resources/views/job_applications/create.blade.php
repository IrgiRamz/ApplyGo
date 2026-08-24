@extends('layouts.app')

@push('styles')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('title', 'Kirim Lamaran')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Kirim Lamaran Kerja</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kirim Lamaran</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(!auth()->user()->hasEmailSetting())
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-warning">
                <i class="ri-error-warning-line me-2"></i>
                <strong>Pengaturan SMTP belum lengkap!</strong> Silakan <a href="{{ route('email-settings.edit') }}" class="alert-link">konfigurasi SMTP</a> terlebih dahulu sebelum mengirim lamaran.
            </div>
        </div>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Form Kirim Lamaran</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('job-applications.store') }}" method="POST">
                    @csrf

                    <!-- Info Perusahaan -->
                    <h6 class="text-muted mb-3">Informasi Perusahaan</h6>
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
                                <label for="job_position" class="form-label">Posisi</label>
                                <input type="text" class="form-control" id="job_position" name="job_position"
                                    value="{{ old('job_position') }}" placeholder="Backend Developer" required>
                            </div>
                        </div>
                    </div>

                    <!-- Email Tujuan -->
                    <h6 class="text-muted mb-3 mt-4">Email Tujuan</h6>
                    <div class="mb-3">
                        <label for="company_email" class="form-label">Email HRD / Perekrut</label>
                        <input type="email" class="form-control" id="company_email" name="company_email"
                            value="{{ old('company_email') }}" placeholder="hrd@contoh.com" required>
                    </div>

                    <!-- Subjek -->
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subjek Email</label>
                        <input type="text" class="form-control" id="subject" name="subject"
                            value="{{ old('subject', 'Lamaran Pekerjaan - {posisi_pekerjaan}') }}"
                            placeholder="Lamaran Pekerjaan - Backend Developer" required>
                        <small class="text-muted">
                            Placeholder: <code>{nama_perusahaan}</code>, <code>{posisi_pekerjaan}</code>
                        </small>
                    </div>

                    <!-- Template & Lampiran -->
                    <h6 class="text-muted mb-3 mt-4">Template & Lampiran</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template_id" class="form-label">Template Email <span class="text-danger">*</span></label>
                                <select class="form-select" id="template_id" name="template_id" required>
                                    <option value="">-- Pilih Template --</option>
                                    @foreach($emailTemplates as $template)
                                        <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                            {{ $template->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="attachment_id" class="form-label">Lampiran CV PDF <span class="text-danger">*</span></label>
                                <select class="form-select" id="attachment_id" name="attachment_id" required>
                                    <option value="">-- Pilih Dokumen --</option>
                                    @foreach($attachmentDocuments as $doc)
                                        <option value="{{ $doc->id }}" {{ old('attachment_id') == $doc->id ? 'selected' : '' }}>
                                            {{ $doc->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Mode Pengiriman -->
                    <h6 class="text-muted mb-3 mt-4">Mode Pengiriman</h6>
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="send_type" id="send_now" value="now" checked>
                            <label class="form-check-label" for="send_now">
                                <i class="ri-send-plane-line me-1"></i> Kirim Sekarang
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="send_type" id="send_scheduled" value="scheduled">
                            <label class="form-check-label" for="send_scheduled">
                                <i class="ri-calendar-line me-1"></i> Jadwalkan
                            </label>
                        </div>
                    </div>

                    <!-- Tanggal & Waktu Penjadwalan -->
                    <div class="row">
                        <div class="col-md-6 mb-3" id="wrapper-scheduled-at" style="display: none;">
                            <label for="scheduled_at" class="form-label">Waktu Pengiriman <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="scheduled_at" name="scheduled_at" placeholder="Pilih Tanggal & Jam">
                            <small class="text-muted">Jika memilih hari ini, jam pengiriman harus lebih dari waktu saat ini.</small>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ route('job-applications.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-success" {{ !auth()->user()->hasEmailSetting() ? 'disabled' : '' }}>
                            <i class="ri-send-plane-line me-1"></i> Kirim Lamaran
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
                <h6>Placeholder di Subjek:</h6>
                <ul class="mb-3">
                    <li><code>{nama_perusahaan}</code></li>
                    <li><code>{posisi_pekerjaan}</code></li>
                </ul>

                <h6>Mode Pengiriman:</h6>
                <ul class="mb-3">
                    <li><strong>Kirim Sekarang:</strong> Email langsung dikirim</li>
                    <li><strong>Jadwalkan:</strong> Email akan dikirim sesuai tanggal & waktu yang dipilih</li>
                </ul>

                <div class="alert alert-info mb-0">
                    <i class="ri-information-line me-2"></i>
                    Scheduler berjalan setiap menit untuk memproses email terjadwal.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    $(document).ready(function () {
        const scheduledPicker = flatpickr("#scheduled_at", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            minDate: "today",
            defaultHour: new Date().getHours(),
            defaultMinute: new Date().getMinutes() + 5,
            onChange: function(selectedDates, dateStr, instance) {
                const now = new Date();
                const selectedDate = selectedDates[0];
                
                if (selectedDate) {
                    // Jika user memilih hari ini, batas minimal waktu adalah waktu sekarang + 2 menit
                    const isToday = selectedDate.toDateString() === now.toDateString();
                    if (isToday) {
                        const minTime = new Date(now.getTime() + 2 * 60000);
                        instance.set('minTime', minTime.getHours() + ':' + minTime.getMinutes());
                    } else {
                        instance.set('minTime', '00:00');
                    }
                }
            }
        });

        // Toggle tampilan input datetime saat radio button / pilihan 'Jadwalkan' dipilih
        $('input[name="send_type"]').on('change', function () {
            if ($(this).val() === 'scheduled') {
                $('#wrapper-scheduled-at').slideDown();
                $('#scheduled_at').prop('required', true);
            } else {
                $('#wrapper-scheduled-at').slideUp();
                $('#scheduled_at').prop('required', false);
            }
        });

        // Trigger initial state
        if ($('input[name="send_type"]:checked').val() === 'scheduled') {
            $('#wrapper-scheduled-at').show();
            $('#scheduled_at').prop('required', true);
        }

        // Show loading SweetAlert when sending email now
        $('form').on('submit', function() {
            var sendType = $('input[name="send_type"]:checked').val();
            if (sendType === 'now') {
                Swal.fire({
                    title: 'Mengirim Email...',
                    text: 'Mohon tunggu sebentar, sistem sedang menghubungkan ke SMTP server.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
        });
    });
</script>
@endpush
