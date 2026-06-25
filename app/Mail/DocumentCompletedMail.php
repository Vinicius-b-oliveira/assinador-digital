<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Document $document)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Documento concluído: '.$this->document->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-completed',
            with: [
                'documentTitle' => $this->document->title,
                'documentUrl' => route('documents.show', $this->document),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('s3', $this->document->certificate_path)
                ->as('certificado-assinaturas.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
