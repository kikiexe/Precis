<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class PresignUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'in:image/webp,image/jpeg,image/png'],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:2097152'], // maksimal 2MB
        ];
    }

    public function messages(): array
    {
        return [
            'filename.required' => 'Nama file wajib diisi.',
            'mime_type.required' => 'Tipe MIME file wajib ditentukan.',
            'mime_type.in' => 'Format file tidak didukung. Gunakan WebP, JPEG, atau PNG.',
            'size_bytes.required' => 'Ukuran file wajib disertakan.',
            'size_bytes.max' => 'Ukuran file tidak boleh melebihi 2MB.',
        ];
    }
}
