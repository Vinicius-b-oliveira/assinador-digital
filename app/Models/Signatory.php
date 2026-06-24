<?php

namespace App\Models;

use App\Enums\SignatoryStatus;
use Database\Factories\SignatoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['document_id', 'name', 'email', 'order', 'token', 'status', 'signed_at', 'ip_address'])]
class Signatory extends Model
{
    /** @use HasFactory<SignatoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SignatoryStatus::class,
            'signed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Signatory $signatory) {
            $signatory->token ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return HasOne<Signature, $this>
     */
    public function signature(): HasOne
    {
        return $this->hasOne(Signature::class);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', SignatoryStatus::Pending);
    }
}
