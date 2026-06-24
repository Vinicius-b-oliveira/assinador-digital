<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\SignatoryStatus;
use App\Mail\DocumentCompletedMail;
use App\Mail\SigningInvitationMail;
use App\Models\Document;
use App\Models\Signatory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

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

    /**
     * @throws Throwable
     */
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
     *
     * @throws Throwable
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

    /**
     * Marca o documento como pendente e convida o primeiro signatário da ordem.
     *
     * @throws Throwable
     */
    public function send(Document $document): void
    {
        DB::transaction(function () use ($document) {
            $document->update(['status' => DocumentStatus::Pending]);

            activity()
                ->performedOn($document)
                ->event('sent')
                ->log('Documento enviado para assinatura');

            $this->dispatchNextInvitation($document);
        });
    }

    /**
     * Convida o próximo signatário pendente na ordem, se houver.
     */
    public function dispatchNextInvitation(Document $document): ?Signatory
    {
        $next = $document->signatories()->pending()->orderBy('order')->first();

        if ($next !== null) {
            Mail::to($next->email)->queue(new SigningInvitationMail($next));
        }

        return $next;
    }

    public function advanceFlow(Document $document): void
    {
        if ($document->status !== DocumentStatus::Pending || ! $document->signatories()->exists()) {
            return;
        }

        if ($document->signatories()->pending()->exists()) {
            $this->dispatchNextInvitation($document);

            return;
        }

        $document->update(['status' => DocumentStatus::Completed]);

        activity()
            ->performedOn($document)
            ->event('completed')
            ->log('Documento concluído — todos assinaram');

        Mail::to($document->user->email)->queue(new DocumentCompletedMail($document));
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
