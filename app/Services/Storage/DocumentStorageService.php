<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToRetrieveMetadata;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentStorageService
{
    private const string DISK = 's3';

    private const string DIRECTORY = 'documents';

    /**
     * Armazena o PDF enviado e devolve o caminho relativo no disco.
     */
    public function store(UploadedFile $file): string
    {
        $path = self::DIRECTORY.'/'.Str::uuid().'.pdf';

        Storage::disk(self::DISK)->put($path, $file->getContent());

        return $path;
    }

    /**
     * Stream do arquivo para exibição inline no navegador.
     */
    public function inlineResponse(string $path, string $downloadName): StreamedResponse
    {
        try {
            if (! Storage::disk(self::DISK)->exists($path)) {
                throw new NotFoundHttpException('Arquivo do documento não encontrado no storage.');
            }

            return Storage::disk(self::DISK)->response(
                $path,
                $downloadName,
                ['Content-Type' => 'application/pdf'],
                'inline',
            );
        } catch (UnableToCheckExistence|UnableToRetrieveMetadata) {
            throw new NotFoundHttpException('Arquivo do documento não encontrado no storage.');
        }
    }

    public function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }
}
