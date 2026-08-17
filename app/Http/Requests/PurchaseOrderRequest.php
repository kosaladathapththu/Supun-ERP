<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('purchases.create');
    }

    public function rules(): array
    {
        $cid = $this->user()->company_id;

        return ['supplier_id' => ['required', Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('company_id', $cid)->where('is_active', true))], 'order_date' => ['required', 'date'], 'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'], 'notes' => ['nullable', 'string', 'max:2000'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $cid)->where('is_active', true))], 'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.unit_cost' => ['required', 'numeric', 'min:0']];
    }
}
