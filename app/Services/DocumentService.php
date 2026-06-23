<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    public function __construct(private DocumentStorageService $storage) {}

    /**
     * @param  array{title: string, description?: string|null, pdf: UploadedFile}  $data
     */
    public function create(array $data, User $user): Document
    {
        $file = $data['pdf'];

        return $user->documents()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $this->storage->store($file),
            'file_original_name' => $file->getClientOriginalName(),
            'status' => DocumentStatus::Draft,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Document>
     */
    public function list(User $user, ?DocumentStatus $status = null): LengthAwarePaginator
    {
        return Document::ownedBy($user)
            ->withCount([
                'signatories',
                'signatories as signatures_count' => fn ($query) => $query->where('status', 'signed'),
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array{title: string, description?: string|null}  $data
     */
    public function update(Document $document, array $data): Document
    {
        $document->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        return $document;
    }

    /**
     * Soft delete — o arquivo no storage é preservado para permitir restauração.
     */
    public function delete(Document $document): void
    {
        $document->delete();
    }
}
