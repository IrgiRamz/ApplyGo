<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailSettingController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\AttachmentDocumentController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\CoverLetterController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// =============================================
// Auth Routes (Guest)
// =============================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// =============================================
// Protected Routes (Auth)
// =============================================
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [JobApplicationController::class, 'dashboard'])->name('dashboard');

    // Profil Pengguna (Edit Profil & Ubah Password)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Pengaturan SMTP Email Pribadi (1-to-1)
    Route::get('/email-settings', [EmailSettingController::class, 'edit'])->name('email-settings.edit');
    Route::put('/email-settings', [EmailSettingController::class, 'update'])->name('email-settings.update');
    Route::post('/email-settings/test', [EmailSettingController::class, 'testConnection'])->name('email-settings.test');

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
    Route::delete('/job-applications/clear-all', [JobApplicationController::class, 'clearAll'])->name('job-applications.clear-all');
    Route::delete('/job-applications/{id}', [JobApplicationController::class, 'destroy'])->name('job-applications.destroy');
    Route::post('/job-applications/{id}/resend', [JobApplicationController::class, 'resend'])->name('job-applications.resend');

    // Cover Letter Generator
    Route::get('/cover-letter/generate', [CoverLetterController::class, 'showForm'])->name('cover-letter.form');
    Route::post('/cover-letter/generate', [CoverLetterController::class, 'generate'])->name('cover-letter.generate');

    // =============================================
    // Admin Exclusive Routes (Proteksi Middleware Kustom 'admin')
    // =============================================
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        // CRUD Manajemen Pengguna
        Route::resource('users', UserController::class);
    });
});
