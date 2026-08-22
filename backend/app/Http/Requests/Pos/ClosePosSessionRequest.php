<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class ClosePosSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pos_session_id' => ['nullable', 'string', 'uuid', 'exists:pos_sessions,id'],
            'closing_cash_actual' => ['required', 'numeric', 'min:0'],
            'closed_by_user_id' => ['nullable', 'string', 'uuid', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'closing_cash_actual.required' => 'Jumlah uang kas fisik aktual wajib diisi.',
            'closing_cash_actual.numeric' => 'Jumlah uang kas fisik aktual harus berupa angka.',
            'closing_cash_actual.min' => 'Jumlah uang kas fisik aktual tidak boleh bernilai negatif.',
        ];
    }
}
