<?php

namespace App\Services;

use App\DTOs\DashboardStatsDTO;
use App\DTOs\DocumentDTO;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Signature;
use App\Models\User;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

readonly class DocumentService
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
     * Agrega indicadores dos documentos do usuário para o dashboard.
     */
    public function statsFor(User $user): DashboardStatsDTO
    {
        $countsByStatus = Document::ownedBy($user)
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $countsByStatus->sum();
        $completed = (int) $countsByStatus->get(DocumentStatus::Completed->value, 0);

        $signaturesCollected = Signature::query()
            ->whereHas('document', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        $recentDocuments = Document::ownedBy($user)
            ->withCount([
                'signatories',
                'signatories as signatures_count' => fn ($query) => $query->where('status', 'signed'),
            ])
            ->latest()
            ->limit(5)
            ->get();

        return new DashboardStatsDTO(
            total: $total,
            draft: (int) $countsByStatus->get(DocumentStatus::Draft->value, 0),
            pending: (int) $countsByStatus->get(DocumentStatus::Pending->value, 0),
            completed: $completed,
            cancelled: (int) $countsByStatus->get(DocumentStatus::Cancelled->value, 0),
            signaturesCollected: $signaturesCollected,
            completionRate: $total > 0 ? (int) round($completed / $total * 100) : 0,
            recentDocuments: $recentDocuments->map(
                fn (Document $document) => DocumentDTO::fromModel($document)->toArray()
            )->all(),
        );
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
