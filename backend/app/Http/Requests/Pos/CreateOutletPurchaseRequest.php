<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOutletPurchaseRequest extends FormRequest
{
    /**
     * otorisasi permohonan
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * aturan validasi pencatatan belanja outlet
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'pos_session_id' => ['nullable', 'uuid', 'exists:pos_sessions,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'category' => [
                'required',
                'string',
                Rule::in(['BAHAN_BAKU_DARURAT', 'OPERASIONAL_TOKO', 'KEBERSIHAN', 'UTILITAS', 'LAINNYA']),
            ],
            'funding_source' => [
                'required',
                'string',
                Rule::in(['CASH_DRAWER', 'EXTERNAL_REIMBURSE']),
            ],
            'receipt_photo_url' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * pesan kustom validasi
     */
    public function messages(): array
    {
        return [
            'item_name.required' => 'Nama item belanja outlet wajib diisi.',
            'unit.required' => 'Satuan kuantitas barang wajib diisi.',
            'quantity.required' => 'Jumlah barang wajib diisi.',
            'unit_price.required' => 'Harga per unit wajib diisi.',
            'category.required' => 'Kategori belanja wajib dipilih.',
            'category.in' => 'Kategori belanja tidak valid.',
            'funding_source.required' => 'Sumber dana pembayaran wajib dipilih.',
            'funding_source.in' => 'Sumber dana pembayaran tidak valid.',
        ];
    }
}
