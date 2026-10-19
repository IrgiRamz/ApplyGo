<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailSettingController;
use App\Models\JobApplication;
use App\Models\EmailTemplate;
use App\Models\AttachmentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JobApplicationApiController extends Controller
{
    /**
     * Endpoint API untuk mengecek riwayat lamaran user terautentikasi
     * GET /api/v1/job-applications
     * Mendukung query filter: ?company_email=...&job_position=...&limit=...
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = JobApplication::where('user_id', $user->id)
            ->with(['attachmentDocument:id,title,file_name']);

        if ($request->filled('company_email')) {
            $email = trim($request->input('company_email'));
            $query->where('recipient_email', 'like', "%{$email}%");
        }

        if ($request->filled('job_position')) {
            $position = trim($request->input('job_position'));
            $query->where('position', 'like', "%{$position}%");
        }

        if ($request->filled('company_name')) {
            $company = trim($request->input('company_name'));
            $query->where('company_name', 'like', "%{$company}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $limit = min((int) $request->input('limit', 20), 100);
        $applications = $query->latest()->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat lamaran berhasil diambil.',
            'data'    => $applications->items(),
            'meta'    => [
                'current_page' => $applications->currentPage(),
                'last_page'    => $applications->lastPage(),
                'per_page'     => $applications->perPage(),
                'total'        => $applications->total(),
            ],
        ], 200);
    }

    /**
     * Endpoint API untuk mengirim lamaran pekerjaan langsung (Instant Send)
     * POST /api/v1/job-applications/send
     * Mendukung: dry_run, idempotency_key, duplicate check 7 hari, dan rate limiting harian.
     */
    public function send(Request $request)
    {
        $user = $request->user();

        // 1. Validasi Input Payload
        $validator = Validator::make($request->all(), [
            'company_name'    => 'required|string|max:255',
            'job_position'    => 'required|string|max:255',
            'company_email'   => 'required|string',
            'subject'         => 'nullable|string|max:255',
            'template_id'     => 'nullable|integer',
            'attachment_id'   => 'nullable|integer',
            'dry_run'         => 'nullable|boolean',
            'idempotency_key' => 'nullable|string|max:255',
        ], [
            'company_name.required'  => 'Field company_name wajib diisi.',
            'job_position.required'  => 'Field job_position wajib diisi.',
            'company_email.required' => 'Field company_email wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi payload gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $isDryRun = $request->boolean('dry_run', false);
        $idempotencyKey = $request->input('idempotency_key') ?: $request->header('Idempotency-Key');

        // 2. Parsing dan Validasi Multi-Email (koma separated)
        $rawEmail = (string) $request->input('company_email');
        $emails = array_filter(array_map('trim', explode(',', $rawEmail)));

        if (empty($emails)) {
            return response()->json([
                'success' => false,
                'message' => 'Field company_email tidak boleh kosong.',
                'errors'  => [
                    'company_email' => ['Alamat email perusahaan wajib diisi.'],
                ],
            ], 422);
        }

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => "Format email '{$email}' tidak valid.",
                    'errors'  => [
                        'company_email' => ["Format email '{$email}' tidak valid."],
                    ],
                ], 422);
            }
        }

        // 3. Pencegahan Lamaran Ganda (Hanya jika BUKAN dry_run)
        if (!$isDryRun) {
            // 3a. Proteksi Idempotency Key (30 Hari Terakhir)
            if ($idempotencyKey) {
                $existingIdempotency = JobApplication::where('user_id', $user->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->first();

                if ($existingIdempotency) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lamaran dengan idempotency_key ini sudah pernah diproses sebelumnya.',
                        'data'    => [
                            'existing_application_id' => $existingIdempotency->id,
                            'status'                  => $existingIdempotency->status,
                            'sent_at'                 => $existingIdempotency->created_at->toIso8601String(),
                        ],
                    ], 409);
                }
            }

            // 3b. Proteksi Guard Kombinasi Email & Posisi (7 Hari Terakhir)
            $sevenDaysAgo = now()->subDays(7);
            $duplicateApp = JobApplication::where('user_id', $user->id)
                ->where('status', 'sent')
                ->where('position', $request->job_position)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->where(function ($query) use ($emails) {
                    foreach ($emails as $email) {
                        $query->orWhere('recipient_email', 'like', "%{$email}%");
                    }
                })
                ->first();

            if ($duplicateApp) {
                return response()->json([
                    'success' => false,
                    'message' => "Peringatan duplikasi: Anda sudah pernah mengirim lamaran untuk posisi '{$request->job_position}' ke email perusahaan ini dalam 7 hari terakhir (ID: {$duplicateApp->id}).",
                    'data'    => [
                        'previous_application_id' => $duplicateApp->id,
                        'sent_at'                 => $duplicateApp->created_at->toIso8601String(),
                    ],
                ], 409);
            }

            // 3c. Rate Limiting Harian (Maksimal 20 Email per Hari)
            $dailyLimit = (int) env('API_DAILY_APPLICATION_LIMIT', 20);
            $sentTodayCount = JobApplication::where('user_id', $user->id)
                ->where('status', 'sent')
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($sentTodayCount >= $dailyLimit) {
                return response()->json([
                    'success' => false,
                    'message' => "Batas kuota harian pengiriman email telah tercapai (maksimal {$dailyLimit} lamaran/hari). Coba lagi besok.",
                    'meta'    => [
                        'daily_limit' => $dailyLimit,
                        'used_today'  => $sentTodayCount,
                        'resets_at'   => Carbon::tomorrow()->toIso8601String(),
                    ],
                ], 429);
            }
        }

        // 4. Cek Konfigurasi SMTP Milik User
        $emailSetting = $user->emailSetting;
        if (!$emailSetting) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaturan SMTP email Anda belum dikonfigurasi di dashboard ApplyGo. Harap lengkapi pengaturan SMTP terlebih dahulu.',
            ], 400);
        }

        // 5. Resolusi Template Email (dengan Fallback Otomatis)
        $template = null;
        if ($request->filled('template_id')) {
            $template = EmailTemplate::where('id', $request->template_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => "Template email dengan ID {$request->template_id} tidak ditemukan untuk akun Anda.",
                ], 404);
            }
        } else {
            // Fallback ke template pertama milik user
            $template = EmailTemplate::where('user_id', $user->id)->first();
        }

        $body = $template ? $template->body : "Halo HRD,\n\nBerikut berkas lamaran saya.";

        // 6. Resolusi Berkas Lampiran / CV PDF (dengan Fallback Otomatis)
        $attachment = null;
        if ($request->filled('attachment_id')) {
            $attachment = AttachmentDocument::where('id', $request->attachment_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$attachment) {
                return response()->json([
                    'success' => false,
                    'message' => "Dokumen lampiran dengan ID {$request->attachment_id} tidak ditemukan untuk akun Anda.",
                ], 404);
            }
        } else {
            // Fallback ke dokumen lampiran terbaru milik user
            $attachment = AttachmentDocument::where('user_id', $user->id)->latest()->first();
        }

        // 7. Subjek Email & Replace Placeholder
        $rawSubject = $request->input('subject') ?: ($template ? $template->title : 'Lamaran Pekerjaan - {posisi_pekerjaan}');
        $subject = str_replace(
            ['{nama_perusahaan}', '{posisi_pekerjaan}'],
            [$request->company_name, $request->job_position],
            $rawSubject
        );

        $body = str_replace(
            ['{nama_perusahaan}', '{posisi_pekerjaan}'],
            [$request->company_name, $request->job_position],
            $body
        );

        // Path Lampiran
        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = Storage::disk('private')->path($attachment->file_path);
        }

        // 8. Penanganan Mode DRY RUN
        if ($isDryRun) {
            return response()->json([
                'success' => true,
                'message' => '[DRY RUN] Validasi dan simulasi pengiriman berhasil. Email tidak dikirim.',
                'dry_run' => true,
                'data'    => [
                    'company_name'    => $request->company_name,
                    'job_position'    => $request->job_position,
                    'recipients'      => $emails,
                    'subject'         => $subject,
                    'template_id'     => $template ? $template->id : null,
                    'template_title'  => $template ? $template->title : 'Default Template',
                    'attachment_id'   => $attachment ? $attachment->id : null,
                    'attachment_name' => $attachment ? $attachment->file_name : null,
                    'idempotency_key' => $idempotencyKey,
                    'preview_body'    => $body,
                ],
            ], 200);
        }

        // 9. Simpan Record Riwayat Lamaran
        $jobApplication = JobApplication::create([
            'user_id'                => $user->id,
            'attachment_document_id' => $attachment ? $attachment->id : null,
            'recipient_email'        => implode(', ', $emails),
            'subject'                => $subject,
            'company_name'           => $request->company_name,
            'position'               => $request->job_position,
            'status'                 => 'pending',
            'scheduled_at'           => now(),
            'idempotency_key'        => $idempotencyKey,
        ]);

        // 10. Pengiriman Email Menggunakan Dynamic Mailer User
        try {
            EmailSettingController::setDynamicMailer($user->id);

            Mail::html(nl2br(e($body)), function ($message) use ($emails, $subject, $attachment, $attachmentPath) {
                $message->to($emails)
                    ->subject($subject);

                if ($attachmentPath && file_exists($attachmentPath)) {
                    $docName = $attachment->name ?? $attachment->title ?? $attachment->file_name;
                    $asName = str_ends_with(strtolower($docName), '.pdf') ? $docName : $docName . '.pdf';
                    $message->attach($attachmentPath, [
                        'as'   => $asName,
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            // Update status menjadi sent
            $jobApplication->update([
                'status'        => 'sent',
                'error_message' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Lamaran pekerjaan berhasil dikirim ke {$jobApplication->company_name}!",
                'dry_run' => false,
                'data'    => [
                    'application_id'  => $jobApplication->id,
                    'company_name'    => $jobApplication->company_name,
                    'position'        => $jobApplication->position,
                    'subject'         => $jobApplication->subject,
                    'recipients'      => $emails,
                    'attachment'      => $attachment ? $attachment->file_name : null,
                    'template'        => $template ? $template->title : 'Default Template',
                    'idempotency_key' => $idempotencyKey,
                    'sent_at'         => now()->toIso8601String(),
                ],
            ], 200);

        } catch (\Throwable $e) {
            $jobApplication->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("API JobApplication send failed ID {$jobApplication->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email melalui server SMTP: ' . $e->getMessage(),
                'data'    => [
                    'application_id' => $jobApplication->id,
                    'status'         => 'failed',
                ],
            ], 500);
        }
    }
}
