# Panduan Teknis & Rencana Implementasi: Job Application Automator & Document Generator

Dokumen ini berisi panduan arsitektur, skema database, pemetaan route, spesifikasi controller, dan potongan kode penting untuk mempermudah pengerjaan proyek oleh developer.

---

## 1. Filosofi Pengembangan & Pola Arsitektur

*   **Pure MVC (Model-View-Controller) Standar Laravel:** Proyek ini menggunakan pola MVC murni tanpa lapisan tambahan seperti *Service Classes*, *Repositories*, *Action Classes*, atau *DTO*.
*   **KISS & Beginner-Friendly:** Semua logika aplikasi ditulis secara terstruktur langsung di dalam **Controller** agar alur aplikasi mudah dipahami oleh junior developer.
*   **Keamanan Data & Isolasi Penuh:** Setiap data yang disimpan harus memiliki keterkaitan dengan user aktif melalui foreign key `user_id` untuk memastikan isolasi data antar akun. Akun bertipe `admin` sekalipun **tidak boleh** mengakses berkas atau log pengguna lain.
*   **SMTP Pribadi & Enkripsi Dua Arah:** Pengguna mengirimkan email lamaran menggunakan akun SMTP pribadi mereka sendiri. Password SMTP disimpan terenkripsi menggunakan enkripsi simetris dua arah Laravel (`Crypt::encryptString`).
*   **Frontend & Theme Stack:**
    *   **Layout & Komponen UI:** Menggunakan template **Velzone** (Bootstrap 5 Admin Dashboard Template) untuk menyajikan tampilan premium, modern, dan responsif.
    *   **DOM & AJAX:** Menggunakan **jQuery** untuk mempermudah manipulasi DOM dan request AJAX sederhana di halaman aplikasi.
    *   **Penyajian Tabel Data:** Menggunakan library **DataTables** (dengan styling Bootstrap 5 bawaan Velzone) pada semua tabel list data utama untuk fitur pencarian, filter, dan paginasi instan di sisi klien.

---

## 2. Struktur Database & Migrasi (Migrations)

Berikut adalah struktur tabel yang dibutuhkan untuk proyek ini:

### 2.1. Tabel `users` (Dengan Role Management)
Menyimpan informasi dasar pengguna dan status perannya.
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('role', ['admin', 'user'])->default('user'); // Kolom Role Management
    $table->timestamps();
});
```

### 2.2. Tabel `email_settings` (Konfigurasi SMTP Pribadi User)
Menyimpan konfigurasi server pengiriman email milik masing-masing user.
```php
Schema::create('email_settings', function (Blueprint $table) {
    $table->id();
    // Hubungan 1-ke-1 privat per akun user
    $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
    $table->string('mail_host');
    $table->integer('mail_port');
    $table->string('mail_username'); // Email pengirim
    $table->text('mail_password'); // Disimpan terenkripsi menggunakan Crypt::encryptString()
    $table->string('mail_encryption')->nullable(); // 'tls', 'ssl', dll.
    $table->string('sender_name'); // Nama pengirim, contoh: "John Doe, S.Kom"
    $table->timestamps();
});
```

### 2.3. Tabel `email_templates`
Menyimpan template email yang bisa digunakan berulang kali dengan placeholder.
```php
Schema::create('email_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title'); // Contoh: "Template Default Backend Developer"
    $table->text('body'); // Menyimpan isi email default dengan placeholder {nama_perusahaan} & {posisi_pekerjaan}
    $table->timestamps();
});
```

### 2.4. Tabel `attachment_documents` (Master PDF Lampiran/CV)
Menyimpan berkas lampiran CV PDF yang diunggah pengguna untuk di-attach ke email lamaran.
```php
Schema::create('attachment_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title'); // Label berkas, contoh: "CV - Backend Laravel"
    $table->string('file_path'); // Path penyimpanan file di storage privat (e.g. 'attachments/cv_user1_xyz.pdf')
    $table->string('file_name'); // Nama file asli saat di-upload (e.g. 'CV_John_Doe.pdf')
    $table->timestamps();
});
```

### 2.5. Tabel `job_applications`
Menyimpan riwayat lamaran, subjek kustom, antrean pengiriman email, dan file CV PDF yang di-attach.
```php
Schema::create('job_applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    
    // Relasi opsional/nullable ke Master Lampiran CV PDF
    $table->foreignId('attachment_document_id')
        ->nullable()
        ->constrained('attachment_documents')
        ->onDelete('set null'); // Jika file PDF dihapus, log lamaran tetap tersimpan
        
    $table->string('recipient_email'); // Email HRD tujuan
    $table->string('subject'); // Subjek email yang diinput manual bebas oleh pengguna
    $table->string('company_name');
    $table->string('position');
    $table->string('status')->default('pending'); // 'pending', 'sent', 'failed'
    $table->timestamp('scheduled_at')->nullable(); // NULL = langsung kirim
    $table->timestamps();
});
```

### 2.6. Tabel `document_templates` (Master Word DOCX)
Menyimpan daftar berkas template word (`.docx`) yang diunggah oleh user untuk di-generate.
```php
Schema::create('document_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name'); // Contoh: "Template CV & Cover Letter 2026"
    $table->string('file_path'); // Path file .docx di local storage (e.g. 'templates/filename.docx')
    $table->timestamps();
});
```

---

## 3. Pemetaan Route (`routes/web.php`)

Gunakan route yang bersih, terstruktur, dan RESTful:

```php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailSettingController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\AttachmentDocumentController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\CoverLetterController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\UserController;

// Auth Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected Routes (Auth)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [JobApplicationController::class, 'dashboard'])->name('dashboard');

    // Pengaturan SMTP Email Pribadi (1-to-1)
    Route::get('/email-settings', [EmailSettingController::class, 'edit'])->name('email-settings.edit');
    Route::put('/email-settings', [EmailSettingController::class, 'update'])->name('email-settings.update');

    // CRUD Template Email
    Route::resource('email-templates', EmailTemplateController::class);

    // CRUD Master Dokumen Lampiran (CV PDF)
    Route::resource('attachment-documents', AttachmentDocumentController::class);

    // CRUD Template Dokumen (.docx)
    Route::resource('document-templates', DocumentTemplateController::class);

    // Job Application (Kirim & Jadwalkan Email)
    Route::get('/job-applications', [JobApplicationController::class, 'index'])->name('job-applications.index');
    Route::get('/job-applications/create', [JobApplicationController::class, 'create'])->name('job-applications.create');
    Route::post('/job-applications', [JobApplicationController::class, 'store'])->name('job-applications.store');

    // Cover Letter Generator
    Route::get('/cover-letter/generate', [CoverLetterController::class, 'showForm'])->name('cover-letter.form');
    Route::post('/cover-letter/generate', [CoverLetterController::class, 'generate'])->name('cover-letter.generate');

    // Admin Exclusive Routes (Proteksi Middleware Kustom 'admin')
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        // CRUD Manajemen Pengguna
        Route::resource('users', UserController::class);
    });
});
```

---

## 4. Struktur View Layout Utama: Integrasi Velzone, jQuery, & DataTables

Buat file layout utama di `resources/views/layouts/app.blade.php` yang mengintegrasikan template admin **Velzone** Bootstrap 5, **jQuery**, dan **DataTables** (via local assets atau CDN terpercaya):

```html
<!DOCTYPE html>
<html lang="id" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">
<head>
    <meta charset="utf-8" />
    <title>@yield('title') | Job Application Automator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- App Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Velzone Bootstrap & Layout CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- DataTables Bootstrap 5 Styling CSS (Bawaan Velzone / CDN) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    @stack('styles')
</head>

<body>
    <!-- Begin page -->
    <div id="layout-wrapper">
        <!-- Topbar & Sidebar Velzone (Disesuaikan berdasarkan hak akses/role) -->
        @include('layouts.partials.topbar')
        @include('layouts.partials.sidebar')

        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            @include('layouts.partials.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- JAVASCRIPT CORE (Velzone & jQuery) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>

    <!-- DataTables JS & Responsive (styling Bootstrap 5) -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- Velzone App JS -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <!-- Script Inisialisasi DataTables per Halaman -->
    @stack('scripts')
</body>
</html>
```

### 4.1. Contoh Inisialisasi DataTables pada View List Halaman (e.g. `index.blade.php`)
Setiap halaman tabel data menggunakan inisialisasi jQuery sederhana untuk mengaktifkan DataTables:
```html
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
            }
        });
    });
</script>
@endpush
```

---

## 5. Daftar Controller & Spesifikasi Fungsi

### 5.1. `AuthController`
*   `showLogin()`: Menampilkan halaman login.
*   `login(Request $request)`: Melakukan validasi kredensial dan inisialisasi session.
*   `showRegister()`: Menampilkan halaman registrasi (default role: `user`).
*   `register(Request $request)`: Membuat user baru dan mengarahkan ke dashboard.
*   `logout()`: Menghapus session dan logout.

### 5.2. `EmailSettingController`
*   `edit()`: Mengambil data konfigurasi SMTP milik user saat ini (`auth()->user()->emailSetting`) dan menampilkannya di form.
*   `update(Request $request)`: Validasi data input SMTP. Mengenkripsi string password menggunakan `Crypt::encryptString()`, kemudian melakukan `updateOrCreate` pada tabel `email_settings` untuk `user_id` yang sedang login.

### 5.3. `EmailTemplateController`
*   `index()`: Menampilkan daftar template milik user yang login (di-render menggunakan DataTables).
*   `create()`: Menampilkan form tambah template.
*   `store(Request $request)`: Validasi dan simpan template email baru ke database.
*   `edit(EmailTemplate $emailTemplate)`: Menampilkan form edit (pastikan `user_id` sesuai).
*   `update(Request $request, EmailTemplate $emailTemplate)`: Validasi dan simpan perubahan.
*   `destroy(EmailTemplate $emailTemplate)`: Hapus template email.

### 5.4. `AttachmentDocumentController`
*   `index()`: Menampilkan daftar file PDF lampiran CV milik user yang aktif (di-render menggunakan DataTables).
*   `create()`: Form upload berkas PDF lampiran.
*   `store(Request $request)`: Validasi unggahan berkas (wajib **PDF**, ukuran maksimal 2MB-5MB), simpan berkas secara privat ke storage lokal, catat informasi ke tabel `attachment_documents`.
*   `destroy(AttachmentDocument $attachmentDocument)`: Hapus berkas fisik dari storage privat dan hapus record dari database.

### 5.5. `DocumentTemplateController`
*   `index()`: Menampilkan daftar template dokumen `.docx` milik user (di-render menggunakan DataTables).
*   `create()`: Form upload template `.docx`.
*   `store(Request $request)`: Validasi upload berkas (hanya `.docx`) dan simpan ke storage.
*   `destroy(DocumentTemplate $documentTemplate)`: Hapus berkas dari storage fisik dan database.

### 5.6. `JobApplicationController`
*   `dashboard()`: Menampilkan ringkasan statistik (jumlah kirim, pending, failed) & grafik ringkas.
*   `index()`: Menampilkan daftar riwayat lamaran & status pengiriman email (di-render menggunakan DataTables).
*   `create()`: Form input lamaran pekerjaan baru (dropdown template email, dropdown dokumen lampiran/CV PDF, input subjek manual bebas, input email HRD, input nama perusahaan, dan input posisi).
*   `store(Request $request)`: 
    *   Validasi input pengiriman.
    *   Cek ketersediaan pengaturan SMTP pribadi user (`auth()->user()->emailSetting`). Jika belum diset, lempar kembali dengan pesan error.
    *   Cek pilihan mode pengiriman (Kirim Sekarang / Besok / Manual).
    *   Jika **Kirim Sekarang**: 
        1. Set konfigurasi SMTP dinamis (on-the-fly) milik user aktif.
        2. Replace placeholder pada body template.
        3. Panggil Mail class dengan subjek manual bebas -> kirim dengan berkas lampiran jika dipilih -> set status `sent`.
    *   Jika **Jadwalkan**: Hitung waktu `scheduled_at` dan simpan baris baru ke DB dengan status `pending`.

### 5.7. `CoverLetterController`
*   `showForm()`: Menampilkan form input untuk pembuatan dokumen (pilih master `.docx`, input nama perusahaan, posisi, dan pilihan format ekspor: DOCX/PDF).
*   `generate(Request $request)`: Proses pembacaan template, penggantian kata kunci (placeholder), konversi format, ekspor dokumen, dan pembersihan file temporer di storage.

### 5.8. `UserController` (Khusus Admin)
*   `index()`: Menampilkan semua daftar pengguna terdaftar di aplikasi (di-render menggunakan DataTables).
*   `create()`: Menampilkan form untuk menambahkan pengguna baru.
*   `store(Request $request)`: Validasi input (nama, email unik, password, role), simpan user baru.
*   `edit(User $user)`: Form edit untuk mengubah profil user, password (opsional), dan perannya.
*   `update(Request $request, User $user)`: Validasi data perubahan dan simpan ke database.
*   `destroy(User $user)`: Menghapus akun pengguna dari database (sekaligus menghapus relasi datanya karena menggunakan `cascade on delete`).

---

## 6. Implementasi Dynamic SMTP & Enkripsi

### 6.1. Cara Enkripsi & Dekripsi Password SMTP di Controller
Simpan password SMTP terenkripsi di `EmailSettingController@update`:
```php
use Illuminate\Support\Facades\Crypt;

$passwordTerenkripsi = Crypt::encryptString($request->mail_password);
```

### 6.2. Snippet Override Konfigurasi Mailer Secara Dinamis (On-The-Fly)
Buat helper method sebelum memanggil `Mail::send()`:

```php
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use App\Models\EmailSetting;

public function setDynamicMailer(int $userId)
{
    $settings = EmailSetting::where('user_id', $userId)->first();

    if (!$settings) {
        throw new \Exception("Pengaturan SMTP pengiriman email belum dikonfigurasi.");
    }

    $smtpPassword = Crypt::decryptString($settings->mail_password);

    Config::set('mail.mailers.smtp.host', $settings->mail_host);
    Config::set('mail.mailers.smtp.port', $settings->mail_port);
    Config::set('mail.mailers.smtp.username', $settings->mail_username);
    Config::set('mail.mailers.smtp.password', $smtpPassword);
    Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption);
    Config::set('mail.from.address', $settings->mail_username);
    Config::set('mail.from.name', $settings->sender_name);

    Mail::purge();
}
```

---

## 7. Mekanisme Scheduler & Mail Attachment

### 7.1. Kelas Mailable (`app/Mail/JobApplicationMail.php`)
```php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Storage;

class JobApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $emailSubject;
    public $emailBody;

    public function __construct(JobApplication $application, string $subject, string $body)
    {
        $this->application = $application;
        $this->emailSubject = $subject;
        $this->emailBody = $body;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job_application',
            with: ['body' => $this->emailBody]
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->application->attachment_document_id && $this->application->attachmentDocument) {
            $filePath = Storage::path($this->application->attachmentDocument->file_path);
            
            if (file_exists($filePath)) {
                $attachments[] = Attachment::fromPath($filePath)
                    ->as($this->application->attachmentDocument->file_name)
                    ->withMime('application/pdf');
            }
        }

        return $attachments;
    }
}
```

### 7.2. Command Artisan Scheduler (`app/Console/Commands/SendScheduledEmails.php`)
```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobApplication;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use App\Mail\JobApplicationMail;

class SendScheduledEmails extends Command
{
    protected $signature = 'emails:send-scheduled';
    protected $description = 'Kirim email lamaran pekerjaan terjadwal dengan SMTP masing-masing user';

    public function handle()
    {
        $applications = JobApplication::with(['attachmentDocument', 'user.emailSetting'])
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($applications as $app) {
            try {
                if (!$app->user || !$app->user->emailSetting) {
                    $app->update(['status' => 'failed']);
                    $this->error("Gagal mengirim lamaran ID {$app->id}: SMTP belum dikonfigurasi.");
                    continue;
                }

                $this->setDynamicMailer($app->user->emailSetting);

                $template = EmailTemplate::where('user_id', $app->user_id)->first();
                $body = $template ? $template->body : "Halo HRD,\nBerikut berkas lamaran saya.";

                $body = str_replace(
                    ['{nama_perusahaan}', '{posisi_pekerjaan}'],
                    [$app->company_name, $app->position],
                    $body
                );

                $subject = $app->subject;
                $subject = str_replace(
                    ['{nama_perusahaan}', '{posisi_pekerjaan}'],
                    [$app->company_name, $app->position],
                    $subject
                );

                Mail::to($app->recipient_email)->send(new JobApplicationMail($app, $subject, $body));
                
                $app->update(['status' => 'sent']);
                $this->info("Email berhasil dikirim ke: {$app->recipient_email} menggunakan SMTP milik {$app->user->email}");
            } catch (\Exception $e) {
                $app->update(['status' => 'failed']);
                $this->error("Gagal mengirim lamaran ID {$app->id}: " . $e->getMessage());
            }
        }
    }

    private function setDynamicMailer($settings)
    {
        $smtpPassword = \Illuminate\Support\Facades\Crypt::decryptString($settings->mail_password);

        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.host', $settings->mail_host);
        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.port', $settings->mail_port);
        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.username', $settings->mail_username);
        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.password', $smtpPassword);
        \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption);
        \Illuminate\Support\Facades\Config::set('mail.from.address', $settings->mail_username);
        \Illuminate\Support\Facades\Config::set('mail.from.name', $settings->sender_name);

        Mail::purge();
    }
}
```

---

## 8. Logika Word & PDF Replacement di Controller

Di bawah ini adalah contoh penulisan logic manipulasi file di dalam `CoverLetterController@generate`:

```php
use Illuminate\Http\Request;
use App\Models\DocumentTemplate;
use PhpOffice\PhpWord\TemplateProcessor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

public function generate(Request $request)
{
    $request->validate([
        'document_template_id' => 'required|exists:document_templates,id',
        'company_name' => 'required|string|max:255',
        'position' => 'required|string|max:255',
        'format' => 'required|in:docx,pdf',
    ]);

    $template = DocumentTemplate::where('user_id', auth()->id())
        ->findOrFail($request->document_template_id);

    $originalPath = Storage::path($template->file_path);
    
    $templateProcessor = new TemplateProcessor($originalPath);
    $templateProcessor->setValue('NAMA_PERUSAHAAN', $request->company_name);
    $templateProcessor->setValue('POSISI', $request->position);

    $tempFileName = 'temp_' . auth()->id() . '_' . time();
    $tempDocxPath = storage_path('app/public/temp/' . $tempFileName . '.docx');
    
    if (!file_exists(storage_path('app/public/temp'))) {
        mkdir(storage_path('app/public/temp'), 0755, true);
    }

    $templateProcessor->saveAs($tempDocxPath);

    if ($request->format === 'docx') {
        return response()->download($tempDocxPath, 'Surat_Lamaran_' . str_replace(' ', '_', $request->company_name) . '.docx')
            ->deleteFileAfterSend(true);
    }

    if ($request->format === 'pdf') {
        try {
            \PhpOffice\PhpWord\Settings::setPdfRendererName(\PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF);
            \PhpOffice\PhpWord\Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

            $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempDocxPath);
            $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
            
            $tempPdfPath = storage_path('app/public/temp/' . $tempFileName . '.pdf');
            $pdfWriter->save($tempPdfPath);

            if (file_exists($tempDocxPath)) {
                unlink($tempDocxPath);
            }

            return response()->download($tempPdfPath, 'Surat_Lamaran_' . str_replace(' ', '_', $request->company_name) . '.pdf')
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            if (file_exists($tempDocxPath)) {
                unlink($tempDocxPath);
            }

            return back()->with('error', 'Gagal memproses file PDF. Pastikan file template valid.');
        }
    }
}
```

---

## 9. Step-by-Step Development Checklist

### Tahap 1: Persiapan Awal
1. `[ ]` Install Laravel fresh: `composer create-project laravel/laravel autolamaran-app`
2. `[ ]` Konfigurasi file `.env` (Koneksi database MySQL).
3. `[ ]` Install packages yang dibutuhkan:
   *   `composer require phpoffice/phpword`
   *   `composer require barryvdh/laravel-dompdf`
4. `[ ]` Impor dan integrasikan asset static template **Velzone** (Bootstrap 5 CSS, JS bundle, icons) ke direktori `public/assets/`.

### Tahap 2: Database & Model Setup
1. `[ ]` Buat migrasi untuk tabel `email_settings`, `email_templates`, `attachment_documents`, `job_applications`, dan `document_templates`.
2. `[ ]` Modifikasi skema tabel `users` bawaan untuk menambahkan kolom `role` enum.
3. `[ ]` Jalankan migrasi: `php artisan migrate`.
4. `[ ]` Buat Model dan definisikan relasi (User hasOne EmailSetting. User hasMany EmailTemplate, AttachmentDocument, JobApplication, DocumentTemplate. JobApplication belongsTo AttachmentDocument).

### Tahap 3: Implementasi Autentikasi & UI Layout (Velzone & jQuery)
1. `[ ]` Rancang halaman Login & Register menggunakan form dan struktur style Bootstrap 5 dari Velzone.
2. `[ ]` Implementasikan logic auth dasar pada `AuthController`.
3. `[ ]` Buat layout base master template di `resources/views/layouts/app.blade.php` dengan menyematkan header, sidebar Velzone, jQuery, dan DataTables CSS/JS bundle.
4. `[ ]` Buat partial view untuk Sidebar & Navbar yang secara dinamis menampilkan menu admin "Data User" hanya jika pengguna log-in memiliki `role === 'admin'`.

### Tahap 3.5: Implementasi Role Middleware & Modul Admin (DataTables)
1. `[ ]` Buat middleware `EnsureUserIsAdmin`.
2. `[ ]` Daftarkan middleware alias `'admin'` di `bootstrap/app.php`.
3. `[ ]` Buat `UserController` untuk CRUD User khusus admin.
4. `[ ]` Buat views CRUD User khusus admin di folder `resources/views/admin/users/`.
5. `[ ]` Inisialisasi **DataTables** pada tabel daftar pengguna di halaman `index.blade.php` menggunakan inisialisasi script jQuery.
6. `[ ]` Hubungkan CRUD User ke route grup `/admin/users` yang dilindungi middleware `admin`.

### Tahap 4: Modul Setup SMTP Pribadi (`email_settings`)
1. `[ ]` Buat `EmailSettingController`.
2. `[ ]` Rancang view form pengaturan SMTP menggunakan card-layout modern Velzone di `resources/views/email_settings/edit.blade.php`.
3. `[ ]` Tulis logika enkripsi (`Crypt::encryptString`) saat update pengaturan di Controller.
4. `[ ]` Tulis logika dynamic mailer configuration override helper.

### Tahap 5: Modul Master Dokumen Lampiran (CV PDF - DataTables)
1. `[ ]` Buat `AttachmentDocumentController`.
2. `[ ]` Rancang view list CV PDF (dengan tabel DataTables untuk pencarian instan) dan form unggah berkas CV PDF (validasi mimes:pdf, max:5120).
3. `[ ]` Tulis logika simpan file privat di Controller menggunakan `store('attachments')` dan rekam ke DB.
4. `[ ]` Buat fungsi hapus berkas fisik dari storage privat saat record dihapus.

### Tahap 6: Modul Template Email & Pengiriman Lamaran (DataTables)
1. `[ ]` Buat CRUD untuk `EmailTemplateController`. Gunakan DataTables untuk list data template email.
2. `[ ]` Buat form pengiriman/penjadwalan email pada `JobApplicationController` (dropdown template email, dropdown pilihan berkas lampiran CV PDF, input email HRD, input subjek kustom bebas, nama PT, dan posisi).
3. `[ ]` Buat kelas Mailable `JobApplicationMail` yang memuat logika penyematan attachment PDF di method `attachments()`.
4. `[ ]` Tulis logika "Kirim Sekarang" langsung memanggil `Mail::send` / `Mail::to` menggunakan Mailable tersebut dengan memuat SMTP user aktif terlebih dahulu.
5. `[ ]` Tulis logika "Jadwalkan" untuk menyimpan subjek kustom beserta waktu target `scheduled_at` dan status `pending` di DB.
6. `[ ]` Buat command `emails:send-scheduled` untuk memeriksa scheduler dan mengirim email terjadwal. Daftarkan ke `routes/console.php`.
7. `[ ]` Rancang halaman list riwayat lamaran (`job-applications.index`) menggunakan DataTables untuk pencarian instan status lamaran (`pending`, `sent`, `failed`).

### Tahap 7: Modul Cover Letter Generator (DataTables)
1. `[ ]` Buat form untuk upload file `.docx` master di halaman Document Template (gunakan DataTables untuk list template Word).
2. `[ ]` Buat form generator surat lamaran di halaman Cover Letter.
3. `[ ]` Tulis logika penggantian placeholder menggunakan `TemplateProcessor` di `CoverLetterController`.
4. `[ ]` Tulis fungsi download file hasil pemrosesan (.docx atau .pdf) serta pembersihan file temp otomatis.

### Tahap 8: Pengujian (Testing)
1. `[ ]` Uji alur login, registrasi, dan isolasi data per user.
2. `[ ]` Uji akses halaman `/admin/users` menggunakan user biasa (harus mengembalikan error 403).
3. `[ ]` Coba buat, edit, dan hapus user melalui menu admin, verifikasi fungsi filter/pencarian DataTables berjalan mulus di halaman user list.
4. `[ ]` Konfigurasikan SMTP pribadi di akun user (gunakan App Password Google Mail).
5. `[ ]` Uji pengiriman email langsung (kirim sekarang) ke alamat email pengetesan (cek apakah email pengirim yang digunakan adalah SMTP pribadi yang di-set dan berkas lampiran PDF ter-attach dengan benar).
6. `[ ]` Uji pengiriman terjadwal dan jalankan scheduler command untuk mengetes dynamic configuration pada background task.
7. `[ ]` Uji generator dokumen Word/PDF.
