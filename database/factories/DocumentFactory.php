<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = $this->faker->words(3, true).'.pdf';

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'file_path' => 'documents/'.Str::uuid().'.pdf',
            'file_original_name' => $originalName,
            'status' => DocumentStatus::Draft,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => DocumentStatus::Draft]);
    }

    public function pending(): static
    {
        return $this->state(['status' => DocumentStatus::Pending]);
    }

    public function completed(): static
    {
        return $this->state(['status' => DocumentStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => DocumentStatus::Cancelled]);
    }

    public function withSignatories(int $count = 2): static
    {
        return $this->has(
            Signatory::factory()->count($count)->sequence(
                fn (Sequence $sequence) => ['order' => $sequence->index + 1]
            ),
            'signatories'
        );
    }

    public function readyToSign(): static
    {
        return $this->pending()->withSignatories(2);
    }
}
