<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'string', 'uuid', 'exists:branches,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo_url' => ['nullable', 'string', 'url'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'ID cabang outlet wajib dipilih.',
            'branch_id.exists' => 'Cabang outlet tidak ditemukan.',
            'latitude.required' => 'Koordinat latitude GPS wajib disertakan.',
            'longitude.required' => 'Koordinat longitude GPS wajib disertakan.',
            'photo_url.url' => 'Format URL foto tidak valid.',
        ];
    }
}
