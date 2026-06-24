<?php

namespace App\Pulse\Cards;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Throwable;

#[Lazy]
class DatabaseHealth extends Card
{
    public function render(): Renderable
    {
        return view('pulse.cards.database-health', [
            'health' => $this->health(),
        ]);
    }

    /**
     * Mede a disponibilidade e a latência do banco com um SELECT simples.
     *
     * @return array{connection: string, online: bool, latency: float|null}
     */
    public function health(): array
    {
        $connection = (string) config('database.default');

        try {
            $start = microtime(true);
            DB::connection()->select('select 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return ['connection' => $connection, 'online' => true, 'latency' => $latency];
        } catch (Throwable) {
            return ['connection' => $connection, 'online' => false, 'latency' => null];
        }
    }
}
