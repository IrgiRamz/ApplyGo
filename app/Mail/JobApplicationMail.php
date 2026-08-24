<?php

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
            $filePath = Storage::disk('private')->path($this->application->attachmentDocument->file_path);

            if (file_exists($filePath)) {
                $attachments[] = Attachment::fromPath($filePath)
                    ->as($this->application->attachmentDocument->file_name)
                    ->withMime('application/pdf');
            }
        }

        return $attachments;
    }
}
