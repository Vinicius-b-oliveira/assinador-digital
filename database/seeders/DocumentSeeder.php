<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\SignatoryStatus;
use App\Models\Document;
use App\Models\Signatory;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $dev = User::where('email', 'dev@dev.com')->firstOrFail();
        $other = User::where('email', 'other@dev.com')->firstOrFail();

        // Rascunhos
        $this->makeDocument($dev, DocumentStatus::Draft, []);
        $this->makeDocument($dev, DocumentStatus::Draft, [SignatoryStatus::Pending, SignatoryStatus::Pending]);

        // Pendente aguardando o primeiro
        $this->makeDocument($dev, DocumentStatus::Pending, [
            SignatoryStatus::Pending,
            SignatoryStatus::Pending,
            SignatoryStatus::Pending,
        ]);

        // Pendente parcial (primeiro já assinou)
        $this->makeDocument($dev, DocumentStatus::Pending, [
            SignatoryStatus::Signed,
            SignatoryStatus::Pending,
            SignatoryStatus::Pending,
        ]);

        // Concluído (todos assinaram)
        $this->makeDocument($dev, DocumentStatus::Completed, [
            SignatoryStatus::Signed,
            SignatoryStatus::Signed,
            SignatoryStatus::Signed,
        ]);

        // Cancelado (segundo recusou)
        $this->makeDocument($dev, DocumentStatus::Cancelled, [
            SignatoryStatus::Signed,
            SignatoryStatus::Declined,
            SignatoryStatus::Pending,
        ]);

        // Documentos de outro usuário (isolamento por dono)
        $this->makeDocument($other, DocumentStatus::Pending, [
            SignatoryStatus::Pending,
            SignatoryStatus::Pending,
        ]);
        $this->makeDocument($other, DocumentStatus::Completed, [
            SignatoryStatus::Signed,
            SignatoryStatus::Signed,
        ]);
    }

    /**
     * Cria um documento com signatários nos status informados (em ordem),
     * registrando uma Signature para cada signatário assinado.
     *
     * @param  array<int, SignatoryStatus>  $signatoryStatuses
     */
    private function makeDocument(User $owner, DocumentStatus $status, array $signatoryStatuses): Document
    {
        $document = Document::factory()->for($owner)->state(['status' => $status])->create();

        foreach ($signatoryStatuses as $index => $signatoryStatus) {
            $signedAt = $signatoryStatus === SignatoryStatus::Signed
                ? Carbon::today()->subDays(random_int(0, 6))->addHours(random_int(8, 18))
                : null;

            $signatory = Signatory::factory()
                ->for($document)
                ->state([
                    'order' => $index + 1,
                    'status' => $signatoryStatus,
                    'signed_at' => $signedAt,
                    'ip_address' => $signatoryStatus === SignatoryStatus::Pending ? null : fake()->ipv4(),
                ])
                ->create();

            if ($signatoryStatus === SignatoryStatus::Signed) {
                Signature::factory()
                    ->for($signatory)
                    ->for($document)
                    ->state([
                        'signer_name' => $signatory->name,
                        'ip_address' => $signatory->ip_address,
                        'signed_at' => $signedAt,
                    ])
                    ->create();
            }
        }

        return $document;
    }
}
