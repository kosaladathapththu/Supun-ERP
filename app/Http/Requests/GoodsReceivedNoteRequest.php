<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoodsReceivedNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('inventory.post');
    }

    public function rules(): array
    {
        $cid = $this->user()->company_id;

        return ['received_date' => ['required', 'date'], 'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where(fn ($q) => $q->where('company_id', $cid)->where('is_active', true))], 'supplier_invoice_number' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:2000'], 'items' => ['required', 'array'], 'items.*.quantity' => ['nullable', 'numeric', 'min:0'], 'items.*.serials' => ['nullable', 'string']];
    }
}
