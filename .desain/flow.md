# Dokumentasi Alur Kerja Aplikasi (Workflow Documentation)
## Job Application Automator & Document Generator

Dokumen ini menjelaskan alur bisnis dan teknis aplikasi secara *end-to-end* (mulai dari interaksi pengguna di Front-End hingga pengolahan data di Back-End/Controller). Dokumen ini ditujukan sebagai panduan pemahaman alur kerja bagi seluruh tim developer, terutama junior developer, agar memiliki pemahaman logis yang sama saat menulis kode.

---

## 1. Arsitektur & Keamanan Multi-User

Aplikasi **Job Application Automator & Document Generator** dirancang dengan arsitektur multi-user terisolasi yang mengedepankan keamanan data:

*   **Role & Hak Akses:**
    1.  **User Biasa (`user`):** Memiliki hak akses penuh untuk mengelola SMTP pribadi, mengunggah master CV PDF, mengelola template email, mengirim/menjadwalkan lamaran, serta meng-generate dokumen surat lamaran.
    2.  **Admin (`admin`):** Memiliki seluruh fitur yang sama seperti user untuk keperluan lamaran kerja pribadinya, ditambah **1 Menu Tambahan eksklusif: Data User (CRUD User & Assign Role)**.
*   **Isolasi Data Penuh:** 
    *   Setiap data transaksi (lamaran, template email, CV PDF, template Word) wajib memiliki relasi `user_id`.
    *   Pengguna dengan role `admin` **TIDAK BISA** melihat, mengubah, atau mengintip berkas CV, konfigurasi SMTP, data template, atau riwayat lamaran milik user lain. Kueri database di Controller untuk fitur reguler selalu dibatasi secara ketat menggunakan `where('user_id', auth()->id())`.
*   **Keamanan Akun SMTP Pribadi:**
    *   Setiap pengguna menggunakan server SMTP (misal Gmail, Outlook, Yahoo) pribadi masing-masing untuk mengirim email lamaran.
    *   Password/App Password SMTP disimpan dalam database dalam bentuk terenkripsi menggunakan **Enkripsi Simetris Dua Arah bawaan Laravel** (`Crypt::encryptString()`).
    *   Saat email akan dikirimkan, password didekripsi secara instan (*on-the-fly*) menggunakan `Crypt::decryptString()`. Ini mencegah kebocoran password mentah jika database diakses oleh pihak yang tidak bertanggung jawab.
*   **Konfigurasi Dinamis (On-The-Fly Override):**
    *   Aplikasi tidak menggunakan driver email global dari file `.env` untuk mengirim lamaran.
    *   Sesaat sebelum email dikirim (baik instan maupun terjadwal), Controller/Scheduler akan memuat data SMTP milik user pengirim, menyetel config Laravel secara dinamis (`Config::set('mail.mailers.smtp...', ...)`), membersihkan cache mailer lama (`Mail::purge()`), dan memicu pengiriman.

---

## 2. Alur Deskriptif Langkah demi Langkah (UI ke Backend)

### 2.1. Registrasi, Login, & Manajemen User (Khusus Admin)
*   **Tujuan:** Manajemen akun pengguna dan otentikasi.
*   **UI:** Form registrasi, login, dan Menu CRUD Data User (khusus admin).
*   **Backend:** 
    *   Registrasi baru default-nya mendapatkan role `'user'`.
    *   Route CRUD user khusus admin dilindungi oleh middleware kustom `EnsureUserIsAdmin`.
    *   Saat admin menghapus sebuah akun pengguna, proses penghapusan data berjenjang (*cascade delete*) secara otomatis menghapus record SMTP, CV, template email, dan riwayat lamaran milik user tersebut dari database.

### 2.2. Setup Konfigurasi SMTP Email Pengirim
*   **Tujuan:** Menghubungkan email pengirim pribadi pengguna ke sistem.
*   **UI:** User membuka menu "Pengaturan SMTP" lalu menginput data: `mail_host`, `mail_port`, `mail_username` (alamat email), `mail_password` (App Password), `mail_encryption` (`tls`/`ssl`), dan `sender_name`.
*   **Backend:**
    1.  `EmailSettingController@update` memvalidasi kelayakan isian data.
    2.  Mengenkripsi password menggunakan `Crypt::encryptString($request->mail_password)`.
    3.  Menyimpan/memperbarui konfigurasi ke tabel `email_settings` dengan menyandingkan `user_id` milik user yang sedang aktif.

### 2.3. Upload Master Dokumen Lampiran (CV PDF)
*   **Tujuan:** Menyimpan berkas CV PDF bervariasi untuk diunggah sebagai lampiran email.
*   **UI:** Halaman "Dokumen Lampiran (CV)", form input nama berkas (label) dan file upload PDF.
*   **Backend:**
    1.  `AttachmentDocumentController@store` memvalidasi input file wajib PDF (`mimes:pdf`) dengan ukuran maksimal 2MB hingga 5MB.
    2.  Menyimpan berkas fisik ke folder privat lokal (`storage/app/private/attachments/`).
    3.  Mencatat record baru ke tabel `attachment_documents` berisi `file_path`, `file_name`, label `title`, dan `user_id`.

### 2.4. Kelola Master Body Template Email
*   **Tujuan:** Menyusun isi pesan email lamaran kerja yang dinamis.
*   **UI:** Halaman CRUD Template Email.
*   **Backend:**
    *   User mengisi body teks template email dengan menyertakan variabel placeholder `{nama_perusahaan}` dan `{posisi_pekerjaan}`.
    *   Teks disimpan mentah ke tabel `email_templates` dan akan diganti secara dinamis saat proses kirim email dipicu.

### 2.5. Pengisian Form Kirim Lamaran Kerja
*   **Tujuan:** Mengirimkan berkas lamaran ke email HRD perusahaan sasaran.
*   **UI:** Form input "Kirim Lamaran Kerja". User mengisi:
    1.  Target Email HRD
    2.  Subjek Email (Ketik Manual Bebas)
    3.  Nama Perusahaan
    4.  Posisi Pekerjaan
    5.  Dropdown Pilihan Template Email
    6.  Dropdown Pilihan 1 Dokumen CV PDF
    7.  Pilihan Waktu Kirim (Kirim Sekarang / Jadwalkan Besok 08:30 / Jadwalkan Manual)
*   **Backend:**
    *   `JobApplicationController@store` memvalidasi semua inputan form.
    *   Memverifikasi apakah user aktif sudah mengeset konfigurasi SMTP pribadinya di tabel `email_settings`. Jika belum, form dikembalikan dengan pesan error.
    *   Jika memilih **Jadwalkan**: Menghitung waktu target dan menyimpannya ke tabel `job_applications` dengan `status = 'pending'` dan `scheduled_at = datetime_target`.
    *   Jika memilih **Kirim Sekarang**: Memulai eksekusi pengiriman email langsung (lihat alur sub-bab 2.6).

### 2.6. Eksekusi Pengiriman Email (Langsung vs Scheduler)
*   **Tujuan:** Proses pengiriman email fisik lengkap dengan lampiran PDF menggunakan server SMTP masing-masing user.
*   **Backend (Kirim Sekarang):**
    1.  Controller memanggil fungsi override SMTP dinamis berdasarkan data `email_settings` user aktif.
    2.  Mendekripsi password SMTP menggunakan `Crypt::decryptString()`.
    3.  Mengatur `Config::set()` untuk driver SMTP mailer Laravel secara dinamis dan membersihkan cache mailer menggunakan `Mail::purge()`.
    4.  Membaca body template email dan mengganti tag `{nama_perusahaan}` & `{posisi_pekerjaan}` dengan data input form.
    5.  Melakukan replacement tag `{nama_perusahaan}` & `{posisi_pekerjaan}` pada subjek kustom yang ditulis manual (jika ada).
    6.  Mengirim email melalui kelas Mailable `JobApplicationMail` yang otomatis menyertakan berkas PDF dari storage privat (menggunakan `Attachment::fromPath`).
    7.  Menyimpan log ke database `job_applications` dengan `status = 'sent'`.
*   **Backend (Scheduler / Cron Job):**
    1.  Artisan command `emails:send-scheduled` dijalankan oleh Cron Job server setiap menit.
    2.  Sistem menyaring baris `job_applications` yang memiliki `status == 'pending'` AND `scheduled_at <= waktu_sekarang`.
    3.  Untuk setiap antrean yang valid, Scheduler memuat konfigurasi SMTP milik user pembuat lamaran tersebut (`email_settings`).
    4.  Mendekripsi password SMTP, melakukan override konfigurasi dinamis Laravel Mailer, dan memanggil `Mail::purge()`.
    5.  Melakukan templating body email dan subjek email kustom milik lamaran bersangkutan.
    6.  Mengirimkan email menggunakan `JobApplicationMail` lengkap dengan berkas PDF lampiran.
    7.  Mengubah status lamaran menjadi `'sent'`. Jika terjadi SMTP error (kredensial salah, timeout), status diubah menjadi `'failed'`.

### 2.7. Generator & Direct Download Surat Lamaran (Word/PDF)
*   **Tujuan:** Membuat berkas lamaran fisik instan berdasarkan file master `.docx`.
*   **UI:** Form Cover Letter Generator (pilih master `.docx`, input nama perusahaan & posisi, pilih format DOCX/PDF).
*   **Backend:**
    1.  `CoverLetterController@generate` mengambil path file master dari tabel `document_templates` milik user aktif.
    2.  Inisialisasi PHPWord `TemplateProcessor` untuk replace tag `${NAMA_PERUSAHAAN}` dan `${POSISI}`.
    3.  Menyimpan hasil ke file temp `.docx`.
    4.  Jika output DOCX: Mengirim file ke browser dengan `response()->download()` dan menghapusnya secara otomatis via `deleteFileAfterSend(true)`.
    5.  Jika output PDF: PHPWord memicu *PDF Writer* bertenaga Dompdf untuk merender `.docx` temporer menjadi `.pdf` temporer. Mengunduh file `.pdf` ke browser dan menghapus semua file temporer di server.

---

## 3. State Flowchart (Text-based / Mermaid)

### 3.1. Siklus Status Pengiriman Email Terjadwal & Dynamic SMTP Setup
```mermaid
stateDiagram-v2
    [*] --> Draft_Input : User Input Form & Pilih CV PDF
    Draft_Input --> Cek_SMTP_Exist : Validasi Ketersediaan SMTP Pribadi
    Cek_SMTP_Exist --> SMTP_Not_Configured : Pengaturan SMTP Kosong
    SMTP_Not_Configured --> Draft_Input : Kembalikan ke Form + Error Warning
    
    Cek_SMTP_Exist --> SMTP_Configured : SMTP Terdaftar
    SMTP_Configured --> Sent_Directly : Pilih "Kirim Sekarang"
    SMTP_Configured --> Pending_Scheduled : Pilih "Jadwalkan" (Simpan status 'pending')
    
    state Sent_Directly {
        [*] --> Load_User_SMTP : Baca email_settings milik User Aktif
        Load_User_SMTP --> Decrypt_Password : Panggil Crypt::decryptString()
        Decrypt_Password --> Config_Mailer_On_The_Fly : Set Config & Mail::purge()
        Config_Mailer_On_The_Fly --> Build_Mail : Render Subjek Kustom & Body Template
        Build_Mail --> Sematkan_Attachment : Lampirkan PDF CV dari Storage Privat
        Sematkan_Attachment --> Execute_SMTP : Kirim email via Mail Facade
    }

    state Pending_Scheduled {
        [*] --> Menunggu_Waktu : Status 'pending' di DB
        Menunggu_Waktu --> Diproses_Scheduler : Cron memanggil emails:send-scheduled (Waktu Tercapai)
        Diproses_Scheduler --> Load_Owner_SMTP : Ambil data email_settings milik Pemilik Lamaran
        Load_Owner_SMTP --> Decrypt_Password_Sched : Panggil Crypt::decryptString()
        Decrypt_Password_Sched --> Config_Mailer_Sched : Set Config & Mail::purge()
        Config_Mailer_Sched --> Build_Mail_Sched : Render Subjek & Body
        Build_Mail_Sched --> Sematkan_Attachment_Sched : Lampirkan PDF CV dari Storage
        Sematkan_Attachment_Sched --> Execute_SMTP_Sched : Kirim email via Mail Facade
    }

    Execute_SMTP --> Sent_Success : Pengiriman Berhasil
    Execute_SMTP --> Sent_Failed : Pengiriman Gagal (SMTP Error)
    Execute_SMTP_Sched --> Sent_Success : Pengiriman Berhasil (Status -> 'sent')
    Execute_SMTP_Sched --> Sent_Failed : Pengiriman Gagal (Status -> 'failed')

    Sent_Success --> [*]
    Sent_Failed --> [*]
```

---

## 4. Penanganan Kasus Gagal (Error Handling & Edge Cases)

### 4.1. Validasi & Pengujian Koneksi SMTP
*   **Masalah:** User salah menginput data host SMTP, port, username, atau lupa mengaktifkan App Password di akun Google Mail mereka, sehingga pengiriman gagal.
*   **Solusi:**
    *   Sebelum menyimpan perubahan data di `EmailSettingController@update`, sistem mencoba membuat koneksi SMTP sementara (*handshake test*) menggunakan parameter baru.
    *   Jika jabat tangan koneksi gagal atau terjadi error autentikasi, sistem membatalkan penyimpanan, dan mengembalikan user ke form SMTP dengan pesan error teknis yang ramah (contoh: *"Kredensial SMTP salah atau port diblokir. Pastikan Anda menggunakan App Password untuk Gmail"*).

### 4.2. Kegagalan SMTP JIT Saat Pengiriman Terjadwal
*   **Masalah:** Saat email terjadwal dieksekusi di background oleh scheduler, server SMTP user mengalami limit pengiriman harian atau koneksi terputus tiba-tiba.
*   **Solusi:**
    *   Seluruh proses inisialisasi SMTP dinamis dan pengiriman email di scheduler dibungkus dengan blok `try-catch`.
    *   Jika eksekusi email gagal, status baris `job_applications` bersangkutan langsung diubah menjadi `'failed'`. 
    *   Scheduler mencatat error secara detail ke file log (`Log::error()`) dan secara otomatis melanjutkan pengiriman untuk antrean lamaran dari user berikutnya tanpa menyebabkan cron job terhenti secara paksa.

### 4.3. Berkas PDF CV Terhapus Fisik di Storage
*   **Masalah:** Database mencatat bahwa aplikasi lamaran memerlukan attachment PDF dengan ID tertentu, tetapi file PDF fisiknya di storage privat terhapus.
*   **Solusi:**
    *   Pada method `attachments()` kelas Mailable `JobApplicationMail`, sistem melakukan validasi fisik berkas menggunakan `file_exists()`.
    *   Jika berkas PDF tidak ditemukan, program mencatat peringatan ke log sistem dan tetap melanjutkan pengiriman email lamaran tanpa menyertakan berkas lampiran tersebut, agar email tidak tertahan selamanya di status pending.

### 4.4. Sanitasi Unggahan File Berbahaya
*   **Masalah:** User mengunggah file skrip berbahaya (misal `.php` atau `.exe`) berkedok berkas CV PDF atau template Word.
*   **Solusi:**
    *   Form unggah CV divalidasi dengan rule: `required|file|mimes:pdf|max:5120` (hanya menerima file PDF dengan limit ukuran 5MB).
    *   Form unggah template dokumen Word divalidasi dengan: `required|file|mimes:docx|max:10240` (hanya menerima file DOCX dengan limit ukuran 10MB).
    *   Setiap kegagalan validasi langsung membatalkan penyimpanan dan mengembalikan respon error di UI.
