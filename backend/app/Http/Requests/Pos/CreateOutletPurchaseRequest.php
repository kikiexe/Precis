<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class CreateOutletPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * aturan validasi pencatatan belanja operasional outlet
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'category' => ['required', 'string', 'in:BAHAN_BAKU_DARURAT,OPERASIONAL_TOKO,KEBERSIHAN,UTILITAS,LAINNYA'],
            'funding_source' => ['required', 'string', 'in:CASH_DRAWER,EXTERNAL_REIMBURSE'],
            'receipt_photo_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'pos_session_id' => ['nullable', 'string', 'uuid'],
            'cashier_user_id' => ['nullable', 'string', 'uuid'],
            'pin' => ['nullable', 'string'],
        ];
    }

    /**
     * pesan kesalahan validasi dalam bahasa indonesia
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'item_name.required' => 'Nama item belanja wajib diisi.',
            'unit.required' => 'Satuan item belanja wajib diisi.',
            'quantity.required' => 'Jumlah kuantitas belanja wajib diisi.',
            'quantity.min' => 'Kuantitas belanja minimal 0.01.',
            'unit_price.required' => 'Harga per satuan belanja wajib diisi.',
            'category.in' => 'Kategori belanja tidak valid.',
            'funding_source.in' => 'Sumber dana belanja harus berupa CASH_DRAWER atau EXTERNAL_REIMBURSE.',
        ];
    }
}
