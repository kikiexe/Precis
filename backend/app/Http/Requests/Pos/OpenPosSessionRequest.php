<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class OpenPosSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cashier_user_id' => ['required', 'string', 'uuid', 'exists:users,id'],
            'pin' => ['required', 'string', 'digits:6'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cashier_user_id.required' => 'ID pengguna kasir wajib disertakan.',
            'cashier_user_id.exists' => 'Data kasir tidak ditemukan.',
            'pin.required' => 'PIN kasir wajib diisi.',
            'pin.digits' => 'PIN kasir harus berupa 6 digit angka.',
            'opening_cash.required' => 'Modal awal kasir wajib diisi.',
            'opening_cash.numeric' => 'Modal awal kasir harus berupa angka.',
            'opening_cash.min' => 'Modal awal kasir tidak boleh bernilai negatif.',
        ];
    }
}
