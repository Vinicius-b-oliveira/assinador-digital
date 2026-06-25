<?php

namespace App\Http\Controllers\Public;

use App\Enums\DocumentStatus;
use App\Enums\SignatoryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignDocumentRequest;
use App\Models\Signatory;
use App\Services\SigningService;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SigningController extends Controller
{
    public function __construct(
        private readonly SigningService $service,
        private readonly DocumentStorageService $storage,
    ) {}

    public function show(Signatory $signatory): Response
    {
        $document = $signatory->document;

        return Inertia::render('Public/Sign', [
            'documentTitle' => $document->title,
            'signatory' => [
                'name' => $signatory->name,
                'email' => $signatory->email,
            ],
            'token' => $signatory->token,
            'fileUrl' => route('public.sign.file', $signatory->token),
            'state' => $this->resolveState($signatory),
        ]);
    }

    public function file(Signatory $signatory): StreamedResponse
    {
        $document = $signatory->document;

        return $this->storage->inlineResponse($document->file_path, $document->file_original_name);
    }

    /**
     * @throws Throwable
     */
    public function sign(SignDocumentRequest $request, Signatory $signatory): RedirectResponse
    {
        $data = $request->validated();

        $this->service->sign(
            $signatory,
            $data['signature_data'],
            $data['signer_name'],
            (string) $request->ip(),
            $request->userAgent(),
        );

        return to_route('public.sign.show', $signatory->token);
    }

    /**
     * @throws Throwable
     */
    public function decline(Request $request, Signatory $signatory): RedirectResponse
    {
        $this->service->decline($signatory, (string) $request->ip());

        return to_route('public.sign.show', $signatory->token);
    }

    /**
     * Estado da tela pública para o signatário atual.
     */
    private function resolveState(Signatory $signatory): string
    {
        return match (true) {
            $signatory->status === SignatoryStatus::Signed => 'signed',
            $signatory->status === SignatoryStatus::Declined => 'declined',
            $signatory->document->status === DocumentStatus::Completed => 'completed',
            $signatory->document->status === DocumentStatus::Cancelled => 'cancelled',
            ! $this->service->isCurrentTurn($signatory) => 'waiting',
            default => 'ready',
        };
    }
}
