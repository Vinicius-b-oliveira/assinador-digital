<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Models\Signatory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSignatoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Document $document */
        $document = $this->route('document') ?? $this->route('signatory')?->document;

        /** @var Signatory|null $signatory */
        $signatory = $this->route('signatory');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('signatories', 'email')
                    ->where('document_id', $document->id)
                    ->ignore($signatory?->id),
            ],
        ];
    }
}
