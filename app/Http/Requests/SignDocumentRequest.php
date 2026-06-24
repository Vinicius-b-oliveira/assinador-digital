<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'signature_data' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
            'signer_name' => ['required', 'string', 'max:255'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signature_data.required' => 'Desenhe a sua assinatura antes de confirmar.',
            'signature_data.regex' => 'A assinatura enviada é inválida.',
            'signer_name.required' => 'Informe o seu nome completo.',
            'accept_terms.accepted' => 'É necessário aceitar os termos para assinar.',
        ];
    }
}
