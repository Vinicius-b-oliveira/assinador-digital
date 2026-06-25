<?php

namespace App\Mail;

use App\Models\Signatory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignedDocumentCopyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Signatory $signatory)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Documento concluído: '.$this->signatory->document->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signed-document-copy',
            with: [
                'signatoryName' => $this->signatory->name,
                'documentTitle' => $this->signatory->document->title,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('s3', $this->signatory->document->certificate_path)
                ->as('certificado-assinaturas.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
