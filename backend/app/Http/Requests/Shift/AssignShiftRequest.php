<?php

declare(strict_types=1);

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class AssignShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shift_template_id' => ['required', 'string', 'uuid', 'exists:shift_templates,id'],
            'assigned_user_id' => ['required', 'string', 'uuid'],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'shift_template_id.required' => 'Template shift wajib dipilih.',
            'shift_template_id.exists' => 'Template shift tidak valid atau tidak ditemukan.',
            'assigned_user_id.required' => 'Karyawan yang ditugaskan wajib dipilih.',
            'assigned_user_id.exists' => 'Karyawan tidak ditemukan.',
            'date.required' => 'Tanggal penugasan shift wajib diisi.',
            'date.date_format' => 'Format tanggal harus YYYY-MM-DD.',
        ];
    }
}
