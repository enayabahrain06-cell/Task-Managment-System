<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CustomerDesignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task    $task,
        public string  $customerName,
        public ?string $customMessage   = null,
        public ?string $adminNote       = null,
        public ?string $senderName      = null,
        public array   $attachmentFiles = [],  // [['path' => 'disk/path', 'name' => 'filename']]
    ) {}

    public function envelope(): Envelope
    {
        $appName = config('app.name');
        return new Envelope(
            subject: "[{$appName}] Your design is ready for review: {$this->task->title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.customer-design');
    }

    public function attachments(): array
    {
        return collect($this->attachmentFiles)
            ->filter(fn($f) => !empty($f['path']) && Storage::disk('public')->exists($f['path']))
            ->map(fn($f) => Attachment::fromStorageDisk('public', $f['path'])->as($f['name']))
            ->values()
            ->all();
    }
}
