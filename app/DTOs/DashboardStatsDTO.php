<?php

namespace App\DTOs;

final readonly class DashboardStatsDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $recentDocuments
     */
    public function __construct(
        public int   $total,
        public int   $draft,
        public int   $pending,
        public int   $completed,
        public int   $cancelled,
        public int   $signaturesCollected,
        public int   $completionRate,
        public array $recentDocuments,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'draft' => $this->draft,
            'pending' => $this->pending,
            'completed' => $this->completed,
            'cancelled' => $this->cancelled,
            'signaturesCollected' => $this->signaturesCollected,
            'completionRate' => $this->completionRate,
            'recentDocuments' => $this->recentDocuments,
        ];
    }
}
