<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'string', 'uuid', 'exists:subscription_plans,id'],
            'billing_cycle' => ['nullable', 'string', 'in:MONTHLY,ANNUAL,monthly,annual'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'ID paket langganan wajib disertakan.',
            'plan_id.exists' => 'Paket langganan tidak ditemukan.',
            'billing_cycle.in' => 'Siklus tagihan harus berupa MONTHLY atau ANNUAL.',
        ];
    }
}
