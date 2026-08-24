<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResendVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Alamat email wajib disertakan.',
            'email.email' => 'Format email tidak valid.',
        ];
    }
}
