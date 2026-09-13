<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobApplication;
use App\Models\EmailTemplate;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use App\Mail\JobApplicationMail;
use Illuminate\Support\Facades\Log;

class SendScheduledEmails extends Command
{
    protected $signature = 'emails:send-scheduled';
    protected $description = 'Kirim email lamaran pekerjaan terjadwal dengan SMTP masing-masing user';

    public function handle()
    {
        $now = now();
        $this->info("Waktu pengecekan server: " . $now->toDateTimeString());

        $applications = JobApplication::with(['attachmentDocument', 'user.emailSetting'])
            ->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->get();

        $this->info("Menemukan {$applications->count()} email terjadwal.");

        foreach ($applications as $app) {
            $this->info("--------------------------------------------------");
            $this->info("Memproses lamaran ID: {$app->id}");
            $this->info("Perusahaan: {$app->company_name} | Posisi: {$app->position}");
            $this->info("Jadwal kirim database: " . $app->scheduled_at);

            try {
                // Cek apakah user dan SMTP settings ada
                if (!$app->user || !$app->user->emailSetting) {
                    $app->update(['status' => 'failed']);
                    $this->error("Gagal mengirim lamaran ID {$app->id}: SMTP belum dikonfigurasi.");
                    Log::error("Scheduled email failed - No SMTP: JobApplication ID {$app->id}");
                    continue;
                }

                // Cek apakah file lampiran ada jika ditentukan
                if ($app->attachment_document_id && $app->attachmentDocument) {
                    $filePath = \Illuminate\Support\Facades\Storage::disk('private')->path($app->attachmentDocument->file_path);
                    if (!file_exists($filePath)) {
                        $this->warn("Peringatan: Berkas lampiran untuk lamaran ID {$app->id} tidak ditemukan di: {$filePath}");
                        Log::warning("Scheduled email warning - Attachment file not found: JobApplication ID {$app->id} at path {$filePath}");
                    }
                }

                // Set dynamic mailer
                $this->setDynamicMailer($app->user->emailSetting);

                // Ambil template email
                $template = EmailTemplate::where('user_id', $app->user_id)->first();
                $body = $template ? $template->body : "Halo HRD,\nBerikut berkas lamaran saya.";

                // Replace placeholder di body
                $body = str_replace(
                    ['{nama_perusahaan}', '{posisi_pekerjaan}'],
                    [$app->company_name, $app->position],
                    $body
                );

                // Replace placeholder di subject
                $subject = $app->subject;
                $subject = str_replace(
                    ['{nama_perusahaan}', '{posisi_pekerjaan}'],
                    [$app->company_name, $app->position],
                    $subject
                );

                // Memecah dan membersihkan daftar email
                $recipients = array_filter(array_map('trim', explode(',', $app->company_email)));

                // Path lampiran
                $attachmentPath = null;
                if ($app->attachment_document_id && $app->attachmentDocument) {
                    $attachmentPath = \Illuminate\Support\Facades\Storage::disk('private')->path($app->attachmentDocument->file_path);
                }

                Mail::html(nl2br(e($body)), function ($message) use ($app, $recipients, $subject, $attachmentPath) {
                    $message->to($recipients)
                            ->subject($subject);

                    if ($attachmentPath && file_exists($attachmentPath)) {
                        $docName = $app->attachmentDocument->name ?? $app->attachmentDocument->title ?? $app->attachmentDocument->file_name;
                        $asName = str_ends_with(strtolower($docName), '.pdf') ? $docName : $docName . '.pdf';
                        $message->attach($attachmentPath, [
                            'as' => $asName,
                            'mime' => 'application/pdf',
                        ]);
                    }
                });

                // Update status menjadi sent
                $app->update([
                    'status' => 'sent',
                    'error_message' => null,
                ]);

                $this->info("[+] Email berhasil dikirim ke: {$app->company_email} menggunakan SMTP milik {$app->user->email}");
                Log::info("Scheduled email sent successfully: JobApplication ID {$app->id} to {$app->company_email}");

            } catch (\Throwable $e) {
                // Update status menjadi failed
                $app->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                $this->error("[-] Gagal kirim ke {$app->company_email}: " . $e->getMessage());
                Log::error("Gagal kirim email ID {$app->id}: " . $e->getMessage());

                // Lanjutkan ke antrean berikutnya
                continue;
            }
        }

        $this->info("--------------------------------------------------");
        $this->info('Proses pengiriman email terjadwal selesai.');
    }

    /**
     * Set dynamic mailer configuration
     */
    private function setDynamicMailer(EmailSetting $settings): void
    {
        $smtpPassword = Crypt::decryptString($settings->mail_password);

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings->mail_host);
        Config::set('mail.mailers.smtp.port', $settings->mail_port);
        Config::set('mail.mailers.smtp.username', $settings->mail_username);
        Config::set('mail.mailers.smtp.password', $smtpPassword);
        Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption);
        Config::set('mail.from.address', $settings->mail_username);
        Config::set('mail.from.name', $settings->sender_name ?? ($settings->user->name ?? 'Pengirim'));

        Mail::purge();
    }
}
