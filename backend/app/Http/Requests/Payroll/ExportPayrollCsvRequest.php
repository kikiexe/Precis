<?php

declare(strict_types=1);

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class ExportPayrollCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'format' => ['nullable', 'string', 'in:BCA,MANDIRI,bca,mandiri'],
            'branch_id' => ['nullable', 'string', 'uuid', 'exists:branches,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_start.required' => 'Tanggal awal periode penggajian wajib diisi.',
            'period_start.date_format' => 'Format tanggal awal harus berupa YYYY-MM-DD.',
            'period_end.required' => 'Tanggal akhir periode penggajian wajib diisi.',
            'period_end.date_format' => 'Format tanggal akhir harus berupa YYYY-MM-DD.',
            'period_end.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal periode.',
            'format.in' => 'Format ekspor bank hanya mendukung BCA atau MANDIRI.',
        ];
    }
}
