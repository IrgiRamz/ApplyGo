<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\JobApplication;
use App\Mail\JobApplicationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class JobApplicationController extends Controller
{
    /**
     * Menampilkan dashboard dengan statistik
     */
    public function dashboard()
    {
        $userId = auth()->id();

        $stats = [
            'total' => JobApplication::where('user_id', $userId)->count(),
            'sent' => JobApplication::where('user_id', $userId)->where('status', 'sent')->count(),
            'pending' => JobApplication::where('user_id', $userId)->where('status', 'pending')->count(),
            'failed' => JobApplication::where('user_id', $userId)->where('status', 'failed')->count(),
        ];

        $recentApplications = JobApplication::where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'recentApplications'));
    }

    /**
     * Menampilkan daftar lamaran
     */
    public function index()
    {
        $jobApplications = JobApplication::where('user_id', auth()->id())->latest()->get();
        return view('job_applications.index', compact('jobApplications'));
    }

    /**
     * Menampilkan form kirim lamaran
     */
    public function create()
    {
        $emailTemplates = EmailTemplate::where('user_id', auth()->id())->get();
        $attachmentDocuments = auth()->user()->attachmentDocuments;

        return view('job_applications.create', compact('emailTemplates', 'attachmentDocuments'));
    }

    /**
     * Menyimpan dan mengirim lamaran
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_name'   => 'required|string|max:255',
            'job_position'   => 'required|string|max:255',
            'company_email'  => 'required|email',
            'subject'        => 'required|string|max:255',
            'template_id'    => 'required|exists:email_templates,id',
            'attachment_id'  => 'required|exists:attachment_documents,id',
            'send_type'      => 'required|in:now,scheduled',
            'scheduled_at'   => 'nullable|required_if:send_type,scheduled|date|after:now',
        ], [
            'scheduled_at.required_if' => 'Waktu jadwal pengiriman wajib diisi jika memilih opsi jadwalkan.',
            'scheduled_at.after'       => 'Waktu jadwal pengiriman harus lebih dari waktu saat ini.',
        ]);

        // Cek apakah SMTP sudah dikonfigurasi
        $emailSetting = auth()->user()->emailSetting;
        if (!$emailSetting) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Silakan lengkapi Pengaturan SMTP email Anda terlebih dahulu sebelum mengirim lamaran.');
        }

        // Hitung scheduled_at jika dijadwalkan, default ke now() jika dikirim langsung
        $scheduledAt = now();
        if ($request->send_type === 'scheduled' && $request->scheduled_at) {
            $scheduledAt = Carbon::parse($request->scheduled_at);
        }

        // Buat record lamaran
        $jobApplication = JobApplication::create([
            'user_id' => auth()->id(),
            'attachment_document_id' => $request->attachment_id,
            'recipient_email' => $request->company_email,
            'subject' => $request->subject,
            'company_name' => $request->company_name,
            'position' => $request->job_position,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
        ]);

        // Jika kirim sekarang, langsung proses pengiriman
        if ($request->send_type === 'now') {
            return $this->sendNow($jobApplication, $request->template_id);
        }

        return redirect()->route('job-applications.index')->with('success', 'Lamaran berhasil dijadwalkan.');
    }

    /**
     * Mengirim email sekarang
     */
    private function sendNow(JobApplication $jobApplication, ?int $emailTemplateId)
    {
        try {
            // Set dynamic mailer
            EmailSettingController::setDynamicMailer($jobApplication->user_id);

            // Ambil template email
            $template = $emailTemplateId
                ? EmailTemplate::where('id', $emailTemplateId)
                    ->where('user_id', $jobApplication->user_id)
                    ->first()
                : null;

            $body = $template ? $template->body : "Halo HRD,\n\nBerikut berkas lamaran saya.";

            // Replace placeholder
            $body = str_replace(
                ['{nama_perusahaan}', '{posisi_pekerjaan}'],
                [$jobApplication->company_name, $jobApplication->position],
                $body
            );

            $subject = str_replace(
                ['{nama_perusahaan}', '{posisi_pekerjaan}'],
                [$jobApplication->company_name, $jobApplication->position],
                $jobApplication->subject
            );

            // Kirim email
            Mail::to($jobApplication->recipient_email)->send(
                new JobApplicationMail($jobApplication, $subject, $body)
            );

            // Update status
            $jobApplication->update([
                'status' => 'sent',
                'error_message' => null,
            ]);

            return redirect()->route('job-applications.index')
                ->with('success', 'Email lamaran berhasil dikirim ke ' . $jobApplication->recipient_email);

        } catch (\Throwable $e) {
            $jobApplication->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            \Illuminate\Support\Facades\Log::error("Gagal kirim email ID {$jobApplication->id}: " . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus satu riwayat lamaran
     */
    public function destroy($id)
    {
        $application = JobApplication::where('user_id', auth()->id())->findOrFail($id);
        $application->delete();

        return redirect()->back()->with('success', 'Riwayat lamaran berhasil dihapus.');
    }

    /**
     * Bersihkan semua riwayat lamaran
     */
    public function clearAll()
    {
        JobApplication::where('user_id', auth()->id())->delete();

        return redirect()->back()->with('success', 'Seluruh riwayat lamaran berhasil dibersihkan.');
    }

    /**
     * Kirim ulang lamaran yang berstatus failed
     */
    public function resend($id)
    {
        $application = JobApplication::with(['attachmentDocument'])
            ->where('user_id', auth()->id())
            ->where('status', 'failed')
            ->findOrFail($id);

        $smtp = \App\Models\EmailSetting::where('user_id', auth()->id())->first();

        if (!$smtp) {
            return redirect()->back()->with('error', 'Silakan lengkapi Pengaturan SMTP email Anda terlebih dahulu sebelum mengirim lamaran.');
        }

        try {
            // Set dynamic mailer
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $smtp->mail_host);
            Config::set('mail.mailers.smtp.port', $smtp->mail_port);
            Config::set('mail.mailers.smtp.encryption', $smtp->mail_encryption);
            Config::set('mail.mailers.smtp.username', $smtp->mail_username);
            Config::set('mail.mailers.smtp.password', Crypt::decryptString($smtp->mail_password));
            Config::set('mail.from.address', $smtp->mail_username);
            Config::set('mail.from.name', $smtp->sender_name ?? auth()->user()->name);

            Mail::purge();

            // Ambil template email (grab the first one or default)
            $template = EmailTemplate::where('user_id', auth()->id())->first();
            $body = $template ? $template->body : "Halo HRD,\n\nBerikut berkas lamaran saya.";

            // Replace placeholder di body
            $body = str_replace(
                ['{nama_perusahaan}', '{posisi_pekerjaan}'],
                [$application->company_name, $application->position],
                $body
            );

            // Replace placeholder di subject
            $subject = str_replace(
                ['{nama_perusahaan}', '{posisi_pekerjaan}'],
                [$application->company_name, $application->position],
                $application->subject
            );

            // Kirim email
            Mail::to($application->recipient_email)->send(
                new JobApplicationMail($application, $subject, $body)
            );

            $application->update([
                'status' => 'sent',
                'error_message' => null,
            ]);

            return redirect()->back()->with('success', 'Email lamaran berhasil dikirim ulang ke ' . $application->company_name);

        } catch (\Throwable $e) {
            $application->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal mengirim ulang email: ' . $e->getMessage());
        }
    }
}
