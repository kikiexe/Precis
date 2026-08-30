<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class RefundOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approver_user_id' => ['required', 'string', 'uuid', 'exists:users,id'],
            'pin' => ['required', 'string', 'digits:6'],
            'reason' => ['required', 'string', 'max:255'],
            'refund_amount' => ['nullable', 'numeric', 'min:0.01'],
            'refund_method' => ['required', 'string', 'in:CASH_DRAWER,QRIS_TRANSFER,STORE_CREDIT'],
        ];
    }

    public function messages(): array
    {
        return [
            'approver_user_id.required' => 'ID approver wajib disertakan.',
            'approver_user_id.exists' => 'Data approver tidak ditemukan.',
            'pin.required' => 'PIN approver wajib diisi.',
            'pin.digits' => 'PIN approver harus berupa 6 digit angka.',
            'reason.required' => 'Alasan pengembalian dana (refund) wajib diisi.',
            'reason.max' => 'Alasan refund maksimal 255 karakter.',
            'refund_amount.numeric' => 'Nominal refund harus berupa angka.',
            'refund_amount.min' => 'Nominal refund minimal Rp 0,01.',
            'refund_method.required' => 'Metode pengembalian dana wajib dipilih.',
            'refund_method.in' => 'Metode refund harus salah satu dari CASH_DRAWER, QRIS_TRANSFER, atau STORE_CREDIT.',
        ];
    }
}
