<?php

declare(strict_types=1);

namespace App\Http\Requests\CashAdvance;

use Illuminate\Foundation\Http\FormRequest;

class CreateCashAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:10000', 'max:20000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal kasbon wajib diisi.',
            'amount.numeric' => 'Nominal kasbon harus berupa angka yang valid.',
            'amount.min' => 'Nominal pengajuan kasbon minimal Rp 10.000.',
            'amount.max' => 'Nominal pengajuan kasbon melebihi batas maksimum Rp 20.000.000.',
        ];
    }
}
