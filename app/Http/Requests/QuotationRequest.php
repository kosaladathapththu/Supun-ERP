<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('sales.create');
    }

    public function rules(): array
    {
        $c = $this->user()->company_id;

        return ['customer_id' => ['required', Rule::exists('customers', 'id')->where(fn ($q) => $q->where('company_id', $c)->where('is_active', 1))], 'quotation_date' => ['required', 'date'], 'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'], 'channel' => ['required', Rule::in(['retail', 'wholesale'])], 'discount_amount' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $c)->where('is_active', 1))], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.unit_price' => ['required', 'numeric', 'min:0']];
    }
}
