<?php

namespace App\Mail;

use App\Models\Signatory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SigningInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Signatory $signatory)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Você foi convidado a assinar: '.$this->signatory->document->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signing-invitation',
            with: [
                'signatory' => $this->signatory,
                'documentTitle' => $this->signatory->document->title,
                'signUrl' => route('public.sign.show', $this->signatory->token),
            ],
        );
    }
}
