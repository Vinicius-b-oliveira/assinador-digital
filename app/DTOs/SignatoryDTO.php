<?php

namespace App\DTOs;

use App\Models\Signatory;
use Illuminate\Support\Collection;

final class SignatoryDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly int $order,
        public readonly string $status,
        public readonly ?string $signedAt,
    ) {}

    public static function fromModel(Signatory $signatory): self
    {
        return new self(
            id: $signatory->id,
            name: $signatory->name,
            email: $signatory->email,
            order: $signatory->order,
            status: $signatory->status->value,
            signedAt: $signatory->signed_at?->toIso8601String(),
        );
    }

    /**
     * @param  Collection<int, Signatory>  $signatories
     * @return array<int, array<string, mixed>>
     */
    public static function collection(Collection $signatories): array
    {
        return $signatories->map(fn (Signatory $signatory) => self::fromModel($signatory)->toArray())->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'order' => $this->order,
            'status' => $this->status,
            'signedAt' => $this->signedAt,
        ];
    }
}
