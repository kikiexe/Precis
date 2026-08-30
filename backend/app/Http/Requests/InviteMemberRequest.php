<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'job_title' => ['required', 'string', 'max:100'],
            'role' => [
                'sometimes',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strtoupper(trim((string) $value)) === 'OWNER') {
                        $fail('Role OWNER tidak dapat ditetapkan secara manual. Kepemilikan workspace ditetapkan secara otomatis saat registrasi.');
                    }
                },
            ],
            'role_id' => ['nullable', 'string', 'uuid'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Alamat email calon staf wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'job_title.required' => 'Jabatan operasional staf wajib diisi.',
            'base_salary.required' => 'Gaji pokok wajib ditentukan.',
            'base_salary.min' => 'Gaji pokok tidak boleh bernilai negatif.',
        ];
    }
}
