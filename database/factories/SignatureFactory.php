<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Signatory;
use App\Models\Signature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Signature>
 */
class SignatureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'signatory_id' => Signatory::factory()->signed(),
            'document_id' => Document::factory()->pending(),
            'signature_data' => 'data:image/png;base64,'.base64_encode('fake-signature'),
            'signer_name' => $this->faker->name(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'signed_at' => now(),
        ];
    }
}
