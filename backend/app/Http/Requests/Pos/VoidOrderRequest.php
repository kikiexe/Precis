<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class VoidOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approver_user_id' => ['required', 'string', 'uuid', 'exists:users,id'],
            'pin' => ['required', 'string', 'digits:6'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'approver_user_id.required' => 'ID approver wajib disertakan.',
            'approver_user_id.exists' => 'Data approver tidak ditemukan.',
            'pin.required' => 'PIN approver wajib diisi.',
            'pin.digits' => 'PIN approver harus berupa 6 digit angka.',
            'reason.required' => 'Alasan pembatalan (void) wajib diisi.',
            'reason.max' => 'Alasan pembatalan maksimal 255 karakter.',
        ];
    }
}
