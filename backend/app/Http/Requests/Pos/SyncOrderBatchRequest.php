<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class SyncOrderBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orders' => ['required', 'array', 'min:1'],
            'orders.*.client_order_id' => ['required', 'string', 'uuid'],
            'orders.*.order_number' => ['required', 'string', 'max:100'],
            'orders.*.total_amount' => ['required', 'numeric', 'min:0'],
            'orders.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'orders.*.final_amount' => ['required', 'numeric', 'min:0'],
            'orders.*.payment_method' => ['required', 'string', 'in:CASH,QRIS,TRANSFER'],
            'orders.*.payment_status' => ['nullable', 'string', 'in:PAID,CANCELLED'],
            'orders.*.pos_session_id' => ['nullable', 'string', 'uuid'],
            'orders.*.cashier_user_id' => ['nullable', 'string', 'uuid'],
            'orders.*.items' => ['required', 'array', 'min:1'],
            'orders.*.items.*.product_id' => ['nullable', 'string', 'uuid'],
            'orders.*.items.*.product_name' => ['required', 'string', 'max:255'],
            'orders.*.items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'orders.*.items.*.quantity' => ['required', 'integer', 'min:1'],
            'orders.*.items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'orders.*.items.*.notes' => ['nullable', 'string', 'max:255'],
            'orders.*.items.*.modifiers' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'orders.required' => 'Daftar transaksi pesanan wajib disertakan.',
            'orders.array' => 'Format payload pesanan harus berupa array.',
            'orders.min' => 'Minimal terdapat 1 transaksi pesanan untuk disinkronisasi.',
            'orders.*.client_order_id.required' => 'Client order ID wajib disertakan untuk setiap transaksi.',
            'orders.*.order_number.required' => 'Nomor pesanan wajib disertakan.',
            'orders.*.payment_method.in' => 'Metode pembayaran harus berupa CASH, QRIS, atau TRANSFER.',
            'orders.*.items.required' => 'Item pesanan wajib disertakan untuk setiap transaksi.',
        ];
    }
}
