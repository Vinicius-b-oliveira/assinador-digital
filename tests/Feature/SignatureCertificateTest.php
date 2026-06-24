<?php

use App\Jobs\GenerateSignatureCertificateJob;
use App\Mail\DocumentCompletedMail;
use App\Mail\SignedDocumentCopyMail;
use App\Models\Document;
use App\Models\Signatory;
use App\Models\Signature;
use App\Services\SignatureCertificateService;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

function certificateAttachment(string $path): Attachment
{
    return Attachment::fromStorageDisk('s3', $path)
        ->as('certificado-assinaturas.pdf')
        ->withMime('application/pdf');
}

test('the service renders a pdf, stores it on s3 and persists the path', function () {
    Storage::fake('s3');
    $document = Document::factory()->completed()->withSignatories(2)->create();

    $document->signatories->each(function (Signatory $signatory) {
        Signature::factory()->for($signatory)->for($signatory->document)->create();
    });

    $path = app(SignatureCertificateService::class)->generate($document);

    expect($path)->toStartWith('certificates/')->toEndWith('.pdf');
    Storage::disk('s3')->assertExists($path);
    expect($document->fresh()->certificate_path)->toBe($path);
    expect(Storage::disk('s3')->get($path))->toStartWith('%PDF');
});

test('the job generates the certificate and notifies the owner with the certificate attached', function () {
    Storage::fake('s3');
    Mail::fake();
    $document = Document::factory()->completed()->withSignatories(1)->create();
    $signatory = $document->signatories()->firstOrFail();
    Signature::factory()->for($signatory)->for($document)->create();

    (new GenerateSignatureCertificateJob($document))->handle(app(SignatureCertificateService::class));

    expect($document->fresh()->certificate_path)->not->toBeNull();
    Storage::disk('s3')->assertExists($document->fresh()->certificate_path);
    Mail::assertQueued(
        DocumentCompletedMail::class,
        fn (DocumentCompletedMail $mail) => $mail->document->is($document)
            && $mail->hasTo($document->user->email)
            && $mail->hasAttachment(certificateAttachment($document->fresh()->certificate_path))
    );
});

test('the job sends a signed copy with the certificate to every signatory', function () {
    Storage::fake('s3');
    Mail::fake();
    $document = Document::factory()->completed()->withSignatories(3)->create();
    $document->signatories->each(function (Signatory $signatory) {
        Signature::factory()->for($signatory)->for($signatory->document)->create();
    });

    (new GenerateSignatureCertificateJob($document))->handle(app(SignatureCertificateService::class));

    $certificatePath = $document->fresh()->certificate_path;

    $document->signatories->each(function (Signatory $signatory) use ($certificatePath) {
        Mail::assertQueued(
            SignedDocumentCopyMail::class,
            fn (SignedDocumentCopyMail $mail) => $mail->signatory->is($signatory)
                && $mail->hasTo($signatory->email)
                && $mail->hasAttachment(certificateAttachment($certificatePath))
        );
    });

    Mail::assertQueuedCount(4);
});
