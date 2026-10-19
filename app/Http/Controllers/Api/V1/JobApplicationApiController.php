<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailSettingController;
use App\Models\JobApplication;
use App\Models\EmailTemplate;
use App\Models\AttachmentDocument;
use App\Mail\JobApplicationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class JobApplicationApiController extends Controller
{
    /**
     * Endpoint API untuk mengirim lamaran pekerjaan langsung (Instant Send)
     * POST /api/v1/job-applications/send
     */
    public function send(Request $request)
    {
        $user = $request->user();

        // 1. Validasi Input Payload
        $validator = Validator::make($request->all(), [
            'company_name'   => 'required|string|max:255',
            'job_position'   => 'required|string|max:255',
            'company_email'  => 'required|string',
            'subject'        => 'nullable|string|max:255',
            'template_id'    => 'nullable|integer',
            'attachment_id'  => 'nullable|integer',
        ], [
            'company_name.required'  => 'Field company_name wajib diisi.',
            'job_position.required'  => 'Field job_position wajib diisi.',
            'company_email.required' => 'Field company_email wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi payload gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 2. Parsing dan Validasi Multi-Email (koma separated)
        $rawEmail = (string) $request->input('company_email');
        $emails = array_filter(array_map('trim', explode(',', $rawEmail)));

        if (empty($emails)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Field company_email tidak boleh kosong.',
                'errors'  => [
                    'company_email' => ['Alamat email perusahaan wajib diisi.'],
                ],
            ], 422);
        }

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Format email '{$email}' tidak valid.",
                    'errors'  => [
                        'company_email' => ["Format email '{$email}' tidak valid."],
                    ],
                ], 422);
            }
        }

        // 3. Cek Konfigurasi SMTP Milik User
        $emailSetting = $user->emailSetting;
        if (!$emailSetting) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pengaturan SMTP email Anda belum dikonfigurasi di dashboard ApplyGo. Harap lengkapi pengaturan SMTP terlebih dahulu.',
            ], 400);
        }

        // 4. Resolusi Template Email (dengan Fallback Otomatis)
        $template = null;
        if ($request->filled('template_id')) {
            $template = EmailTemplate::where('id', $request->template_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$template) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Template email dengan ID {$request->template_id} tidak ditemukan untuk akun Anda.",
                ], 404);
            }
        } else {
            // Fallback ke template pertama milik user
            $template = EmailTemplate::where('user_id', $user->id)->first();
        }

        $body = $template ? $template->body : "Halo HRD,\n\nBerikut berkas lamaran saya.";

        // 5. Resolusi Berkas Lampiran / CV PDF (dengan Fallback Otomatis)
        $attachment = null;
        if ($request->filled('attachment_id')) {
            $attachment = AttachmentDocument::where('id', $request->attachment_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$attachment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Dokumen lampiran dengan ID {$request->attachment_id} tidak ditemukan untuk akun Anda.",
                ], 404);
            }
        } else {
            // Fallback ke dokumen lampiran terbaru milik user
            $attachment = AttachmentDocument::where('user_id', $user->id)->latest()->first();
        }

        // 6. Subjek Email & Replace Placeholder
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

        // 7. Simpan 1 Record Riwayat Lamaran
        $jobApplication = JobApplication::create([
            'user_id'                => $user->id,
            'attachment_document_id' => $attachment ? $attachment->id : null,
            'recipient_email'        => implode(', ', $emails),
            'subject'                => $subject,
            'company_name'           => $request->company_name,
            'position'               => $request->job_position,
            'status'                 => 'pending',
            'scheduled_at'           => now(),
        ]);

        // 8. Pengiriman Email Menggunakan Dynamic Mailer User
        try {
            EmailSettingController::setDynamicMailer($user->id);

            $attachmentPath = null;
            if ($attachment) {
                $attachmentPath = Storage::disk('private')->path($attachment->file_path);
            }

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
                'status'  => 'success',
                'message' => "Lamaran pekerjaan berhasil dikirim ke {$jobApplication->company_name}!",
                'data'    => [
                    'application_id' => $jobApplication->id,
                    'company_name'   => $jobApplication->company_name,
                    'position'       => $jobApplication->position,
                    'subject'        => $jobApplication->subject,
                    'recipients'     => $emails,
                    'attachment'     => $attachment ? $attachment->file_name : null,
                    'template'       => $template ? $template->title : 'Default Template',
                    'sent_at'        => now()->toIso8601String(),
                ],
            ], 200);

        } catch (\Throwable $e) {
            $jobApplication->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("API JobApplication send failed ID {$jobApplication->id}: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengirim email melalui server SMTP: ' . $e->getMessage(),
                'data'    => [
                    'application_id' => $jobApplication->id,
                    'status'         => 'failed',
                ],
            ], 500);
        }
    }
}
