<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderSignatoriesRequest;
use App\Http\Requests\StoreSignatoryRequest;
use App\Jobs\SendSignatureReminderJob;
use App\Models\Document;
use App\Models\Signatory;
use App\Services\SignatoryService;
use Illuminate\Http\RedirectResponse;
use Throwable;

class SignatoryController extends Controller
{
    public function __construct(private readonly SignatoryService $service) {}

    public function store(StoreSignatoryRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('manage', [Signatory::class, $document]);

        $this->service->add($document, $request->validated());

        return to_route('documents.show', $document);
    }

    public function update(StoreSignatoryRequest $request, Signatory $signatory): RedirectResponse
    {
        $this->authorize('update', $signatory);

        $this->service->update($signatory, $request->validated());

        return to_route('documents.show', $signatory->document);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Signatory $signatory): RedirectResponse
    {
        $this->authorize('delete', $signatory);

        $document = $signatory->document;
        $this->service->remove($signatory);

        return to_route('documents.show', $document);
    }

    /**
     * @throws Throwable
     */
    public function reorder(ReorderSignatoriesRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('manage', [Signatory::class, $document]);

        $this->service->reorder($document, $request->validated('signatories'));

        return to_route('documents.show', $document);
    }

    public function remind(Signatory $signatory): RedirectResponse
    {
        $this->authorize('remind', $signatory);

        SendSignatureReminderJob::dispatch($signatory);

        return back();
    }
}
