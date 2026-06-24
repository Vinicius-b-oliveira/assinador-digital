<?php

namespace App\Http\Controllers;

use App\DTOs\ActivityDTO;
use App\DTOs\DocumentDTO;
use App\DTOs\SignatoryDTO;
use App\Enums\DocumentStatus;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Services\DocumentService;
use App\Services\SignatoryService;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $service,
        private readonly SignatoryService $signatoryService,
        private readonly DocumentStorageService $storage,
    ) {}

    public function index(Request $request): Response
    {
        $status = DocumentStatus::tryFrom((string) $request->query('status'));

        $documents = $this->service->list($request->user(), $status);

        return Inertia::render('Documents/Index', [
            'documents' => $documents->through(
                fn (Document $document) => DocumentDTO::fromModel($document)->toArray()
            ),
            'filters' => ['status' => $status?->value],
            'statuses' => array_map(fn (DocumentStatus $case) => $case->value, DocumentStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Documents/Create');
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $document = $this->service->create($request->validated(), $request->user());

        return to_route('documents.show', $document);
    }

    public function show(Document $document): Response
    {
        $this->authorize('view', $document);

        $document->load('signatories')->loadCount([
            'signatories',
            'signatories as signatures_count' => fn ($query) => $query->where('status', 'signed'),
        ]);

        $activities = Activity::forSubject($document)->with('causer')->latest()->get();

        return Inertia::render('Documents/Show', [
            'document' => DocumentDTO::fromModel($document)->toArray(),
            'signatories' => SignatoryDTO::collection($document->signatories),
            'activities' => ActivityDTO::collection($activities),
            'fileUrl' => route('documents.file', $document),
        ]);
    }

    public function edit(Document $document): Response
    {
        $this->authorize('update', $document);

        return Inertia::render('Documents/Edit', [
            'document' => DocumentDTO::fromModel($document)->toArray(),
        ]);
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $this->service->update($document, $request->validated());

        return to_route('documents.show', $document);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $this->service->delete($document);

        return to_route('documents.index');
    }

    /**
     * @throws Throwable
     */
    public function send(Document $document): RedirectResponse
    {
        $this->authorize('send', $document);

        $this->signatoryService->send($document);

        return to_route('documents.show', $document);
    }

    public function file(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return $this->storage->inlineResponse($document->file_path, $document->file_original_name);
    }
}
