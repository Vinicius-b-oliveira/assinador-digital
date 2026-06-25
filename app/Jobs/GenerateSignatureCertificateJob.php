<?php

namespace App\Jobs;

use App\Mail\DocumentCompletedMail;
use App\Mail\SignedDocumentCopyMail;
use App\Models\Document;
use App\Models\Signatory;
use App\Services\SignatureCertificateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class GenerateSignatureCertificateJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document)
    {
        $this->afterCommit();
    }

    public function handle(SignatureCertificateService $certificates): void
    {
        $certificates->generate($this->document);

        Mail::to($this->document->user->email)->queue(new DocumentCompletedMail($this->document));

        $this->document->signatories()->get()->each(function (Signatory $signatory) {
            $signatory->setRelation('document', $this->document);

            Mail::to($signatory->email)->queue(new SignedDocumentCopyMail($signatory));
        });
    }
}
