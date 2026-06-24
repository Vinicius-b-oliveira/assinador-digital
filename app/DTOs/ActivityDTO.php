<?php

namespace App\DTOs;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

final readonly class ActivityDTO
{
    public function __construct(
        public int     $id,
        public ?string $event,
        public string  $description,
        public ?string $causer,
        public ?string $signatory,
        public ?string $ip,
        public string  $createdAt,
    ) {}

    public static function fromModel(Activity $activity): self
    {
        return new self(
            id: $activity->id,
            event: $activity->event,
            description: $activity->description,
            causer: $activity->causer?->name,
            signatory: $activity->properties?->get('signatory'),
            ip: $activity->properties?->get('ip'),
            createdAt: $activity->created_at->toIso8601String(),
        );
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<int, array<string, mixed>>
     */
    public static function collection(Collection $activities): array
    {
        return $activities->map(fn (Activity $activity) => self::fromModel($activity)->toArray())->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'description' => $this->description,
            'causer' => $this->causer,
            'signatory' => $this->signatory,
            'ip' => $this->ip,
            'createdAt' => $this->createdAt,
        ];
    }
}
