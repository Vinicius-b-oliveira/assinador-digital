<?php

namespace App\DTOs;

use App\Models\Document;
use Illuminate\Support\Collection;

final class DocumentDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $fileOriginalName,
        public readonly string $createdAt,
        public readonly int $signatoryCount,
        public readonly int $signedCount,
    ) {}

    public static function fromModel(Document $document): self
    {
        return new self(
            id: $document->id,
            title: $document->title,
            description: $document->description,
            status: $document->status->value,
            fileOriginalName: $document->file_original_name,
            createdAt: $document->created_at->toIso8601String(),
            // TODO(DevB): contagens vêm de withCount(['signatories','signatures']) após o merge de Signatory.
            signatoryCount: $document->signatories_count ?? 0,
            signedCount: $document->signatures_count ?? 0,
        );
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return array<int, array<string, mixed>>
     */
    public static function collection(Collection $documents): array
    {
        return $documents->map(fn (Document $document) => self::fromModel($document)->toArray())->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'fileOriginalName' => $this->fileOriginalName,
            'createdAt' => $this->createdAt,
            'signatoryCount' => $this->signatoryCount,
            'signedCount' => $this->signedCount,
        ];
    }
}
