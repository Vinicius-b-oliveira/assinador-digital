<?php

namespace App\Pulse\Cards;

use App\Models\Signature;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class SignaturesCollected extends Card
{
    private const int DAYS = 7;

    public function render(): Renderable
    {
        $perDay = $this->perDay();

        return view('pulse.cards.signatures-collected', [
            'perDay' => $perDay,
            'total' => $perDay->sum('total'),
        ]);
    }

    /**
     * Assinaturas coletadas por dia nos últimos dias, sem lacunas.
     *
     * @return Collection<int, array{date: string, label: string, total: int}>
     */
    public function perDay(): Collection
    {
        $since = Carbon::today()->subDays(self::DAYS - 1);

        $counts = Signature::query()
            ->where('signed_at', '>=', $since)
            ->get(['signed_at'])
            ->countBy(fn (Signature $signature): string => $signature->signed_at->toDateString());

        return collect(range(0, self::DAYS - 1))
            ->map(function (int $offset) use ($since, $counts): array {
                $date = $since->copy()->addDays($offset);

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('d/m'),
                    'total' => (int) $counts->get($date->toDateString(), 0),
                ];
            });
    }
}
