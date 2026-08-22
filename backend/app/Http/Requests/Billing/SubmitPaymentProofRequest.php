<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_name' => ['required', 'string', 'max:255'],
            'transfer_amount' => ['required', 'numeric', 'min:1'],
            'proof_image_url' => ['required', 'string', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account_name.required' => 'Nama pemilik rekening pengirim wajib diisi.',
            'transfer_amount.required' => 'Nominal transfer wajib diisi.',
            'transfer_amount.numeric' => 'Nominal transfer harus berupa angka.',
            'proof_image_url.required' => 'URL bukti transfer pembayaran wajib disertakan.',
        ];
    }
}
