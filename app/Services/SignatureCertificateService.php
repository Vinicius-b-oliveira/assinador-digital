<?php

namespace App\Services;

use App\Models\Document;
use App\Services\Storage\DocumentStorageService;
use Barryvdh\DomPDF\Facade\Pdf;

readonly class SignatureCertificateService
{
    public function __construct(private DocumentStorageService $storage) {}

    /**
     * Renderiza o certificado de assinaturas em PDF, armazena no disco e
     * persiste o caminho no documento. Devolve o caminho relativo gerado.
     */
    public function generate(Document $document): string
    {
        $signatures = $document->signatures()
            ->with('signatory')
            ->orderBy('signed_at')
            ->get();

        $contents = Pdf::loadView('pdf.signature-certificate', [
            'document' => $document,
            'signatures' => $signatures,
            'generatedAt' => now(),
        ])->output();

        $path = $this->storage->storeCertificate($contents);

        $document->update(['certificate_path' => $path]);

        return $path;
    }
}
