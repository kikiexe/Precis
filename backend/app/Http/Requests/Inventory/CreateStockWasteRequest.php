<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStockWasteRequest extends FormRequest
{
    /**
     * otorisasi permohonan
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * aturan validasi pencatatan stock waste
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:50'],
            'cost_per_unit' => ['required', 'numeric', 'min:0'],
            'total_loss_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => [
                'required',
                'string',
                Rule::in(['EXPIRED', 'SPOILED', 'ACCIDENT_SPILL', 'BARISTA_MISTAKE', 'QC_REJECT', 'OTHER']),
            ],
            'photo_url' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * pesan kustom validasi
     */
    public function messages(): array
    {
        return [
            'item_name.required' => 'Nama item barang / bahan baku yang terbuang wajib diisi.',
            'quantity.required' => 'Jumlah kuantitas terbuang wajib diisi.',
            'quantity.min' => 'Jumlah kuantitas terbuang minimal 0.01.',
            'unit.required' => 'Satuan kuantitas barang wajib diisi.',
            'cost_per_unit.required' => 'Harga modal satuan barang wajib diisi.',
            'reason.required' => 'Alasan barang rusak/terbuang wajib dipilih.',
            'reason.in' => 'Alasan terbuang tidak valid.',
        ];
    }
}
