<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\SignatoryStatus;
use App\Mail\SignatureRecordedMail;
use App\Models\Signatory;
use App\Models\Signature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

readonly class SigningService
{
    public function __construct(private SignatoryService $signatoryService) {}

    /**
     * Verifica se é a vez deste signatário assinar (ordem sequencial).
     */
    public function isCurrentTurn(Signatory $signatory): bool
    {
        $document = $signatory->document;

        if ($document->status !== DocumentStatus::Pending) {
            return false;
        }

        if ($signatory->status !== SignatoryStatus::Pending) {
            return false;
        }

        return ! $document->signatories()
            ->pending()
            ->where('order', '<', $signatory->order)
            ->exists();
    }

    /**
     * Registra a assinatura do signatário e avança o fluxo do documento.
     *
     * @throws Throwable
     */
    public function sign(
        Signatory $signatory,
        string $signatureData,
        string $signerName,
        string $ip,
        ?string $userAgent,
    ): Signature {
        return DB::transaction(function () use ($signatory, $signatureData, $signerName, $ip, $userAgent) {
            $locked = Signatory::query()->lockForUpdate()->findOrFail($signatory->id);

            $this->assertItIsTheirTurn($locked);

            $signature = $locked->signature()->create([
                'document_id' => $locked->document_id,
                'signature_data' => $signatureData,
                'signer_name' => $signerName,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'signed_at' => now(),
            ]);

            $locked->update([
                'status' => SignatoryStatus::Signed,
                'signed_at' => now(),
                'ip_address' => $ip,
            ]);

            activity()
                ->performedOn($locked->document)
                ->withProperties(['ip' => $ip, 'signatory' => $locked->name, 'email' => $locked->email])
                ->event('signed')
                ->log($locked->name.' assinou o documento');

            $this->signatoryService->advanceFlow($locked->document);

            Mail::to($locked->document->user->email)->queue(new SignatureRecordedMail($locked));

            return $signature;
        });
    }

    /**
     * Recusa a assinatura e cancela o documento.
     *
     * @throws Throwable
     */
    public function decline(Signatory $signatory, string $ip): void
    {
        DB::transaction(function () use ($signatory, $ip) {
            $locked = Signatory::query()->lockForUpdate()->findOrFail($signatory->id);

            $this->assertItIsTheirTurn($locked);

            $locked->update([
                'status' => SignatoryStatus::Declined,
                'ip_address' => $ip,
            ]);

            activity()
                ->performedOn($locked->document)
                ->withProperties(['ip' => $ip, 'signatory' => $locked->name, 'email' => $locked->email])
                ->event('declined')
                ->log($locked->name.' recusou a assinatura');

            $locked->document->update(['status' => DocumentStatus::Cancelled]);
        });
    }

    private function assertItIsTheirTurn(Signatory $signatory): void
    {
        if (! $this->isCurrentTurn($signatory)) {
            throw ValidationException::withMessages([
                'signature' => 'Não é a vez deste signatário ou o documento não está mais disponível para assinatura.',
            ]);
        }
    }
}
