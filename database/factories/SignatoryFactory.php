<?php

namespace Database\Factories;

use App\Enums\SignatoryStatus;
use App\Models\Document;
use App\Models\Signatory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Signatory>
 */
class SignatoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'order' => 1,
            'token' => (string) Str::uuid(),
            'status' => SignatoryStatus::Pending,
            'signed_at' => null,
            'ip_address' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => SignatoryStatus::Pending,
            'signed_at' => null,
            'ip_address' => null,
        ]);
    }

    public function signed(): static
    {
        return $this->state([
            'status' => SignatoryStatus::Signed,
            'signed_at' => now(),
            'ip_address' => $this->faker->ipv4(),
        ]);
    }

    public function declined(): static
    {
        return $this->state([
            'status' => SignatoryStatus::Declined,
            'signed_at' => null,
            'ip_address' => null,
        ]);
    }
}
