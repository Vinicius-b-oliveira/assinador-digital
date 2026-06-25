<?php

namespace App\Pulse\Cards;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Contracts\Support\Renderable;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class DocumentsByStatus extends Card
{
    public function render(): Renderable
    {
        $statuses = $this->statuses();

        return view('pulse.cards.documents-by-status', [
            'statuses' => $statuses,
            'total' => array_sum($statuses),
            'completionRate' => $this->completionRate(),
        ]);
    }

    /**
     * Contagem de documentos por status, garantindo todos os casos do enum.
     *
     * @return array<string, int>
     */
    public function statuses(): array
    {
        $counts = Document::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(DocumentStatus::cases())
            ->mapWithKeys(fn (DocumentStatus $status): array => [
                $status->value => (int) $counts->get($status->value, 0),
            ])
            ->all();
    }

    /**
     * Percentual de documentos concluídos sobre o total.
     */
    public function completionRate(): float
    {
        $statuses = $this->statuses();
        $total = array_sum($statuses);

        if ($total === 0) {
            return 0.0;
        }

        return round(($statuses[DocumentStatus::Completed->value] / $total) * 100, 1);
    }
}
