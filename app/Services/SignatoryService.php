<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\SignatoryStatus;
use App\Models\Document;
use App\Models\Signatory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SignatoryService
{
    /**
     * @param  array{name: string, email: string}  $data
     */
    public function add(Document $document, array $data): Signatory
    {
        $nextOrder = ((int) $document->signatories()->max('order')) + 1;

        return $document->signatories()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'order' => $nextOrder,
            'status' => SignatoryStatus::Pending,
        ]);
    }

    /**
     * @param  array{name: string, email: string}  $data
     */
    public function update(Signatory $signatory, array $data): Signatory
    {
        $signatory->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return $signatory;
    }

    public function remove(Signatory $signatory): void
    {
        DB::transaction(function () use ($signatory) {
            $document = $signatory->document;

            $signatory->delete();
            $this->compactOrder($document);
        });
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Document $document, array $orderedIds): void
    {
        DB::transaction(function () use ($document, $orderedIds) {
            $currentIds = $document->signatories()->pluck('id')->sort()->values()->all();
            $receivedIds = collect($orderedIds)->sort()->values()->all();

            if ($currentIds !== $receivedIds) {
                throw ValidationException::withMessages([
                    'signatories' => 'A ordenação deve incluir todos os signatários do documento.',
                ]);
            }

            foreach (array_values($orderedIds) as $index => $id) {
                $document->signatories()
                    ->whereKey($id)
                    ->update(['order' => $index + 1]);
            }
        });
    }

    public function advanceFlow(Document $document): void
    {
        if ($document->status !== DocumentStatus::Pending || ! $document->signatories()->exists()) {
            return;
        }

        if (! $document->signatories()->pending()->exists()) {
            $document->update(['status' => DocumentStatus::Completed]);
        }
    }

    private function compactOrder(Document $document): void
    {
        $document->signatories()
            ->orderBy('order')
            ->get()
            ->values()
            ->each(fn (Signatory $signatory, int $index) => $signatory->update(['order' => $index + 1]));
    }
}
