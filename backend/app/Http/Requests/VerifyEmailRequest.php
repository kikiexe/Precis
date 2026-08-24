<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Token verifikasi email wajib disertakan.',
            'token.size' => 'Format token verifikasi tidak valid.',
        ];
    }
}
