<?php

namespace App\Models;

use Database\Factories\SignatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['signatory_id', 'document_id', 'signature_data', 'signer_name', 'ip_address', 'user_agent', 'signed_at'])]
class Signature extends Model
{
    /** @use HasFactory<SignatureFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(Signatory::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
