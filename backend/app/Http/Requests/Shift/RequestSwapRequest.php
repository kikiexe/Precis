<?php

declare(strict_types=1);

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class RequestSwapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shift_assignment_id' => ['required', 'string', 'uuid', 'exists:shift_assignments,id'],
            'target_user_id' => ['required', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'shift_assignment_id.required' => 'ID penugasan shift wajib diisi.',
            'shift_assignment_id.exists' => 'Penugasan shift tidak ditemukan.',
            'target_user_id.required' => 'Karyawan pengganti wajib ditentukan.',
            'target_user_id.exists' => 'Karyawan pengganti tidak ditemukan.',
        ];
    }
}
