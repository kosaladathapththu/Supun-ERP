<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DirectPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('purchases.create');
    }

    public function rules(): array
    {
        $company = $this->user()->company_id;

        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('company_id', $company)->where('is_active', true))],
            'supplier_invoice_number' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where(fn ($q) => $q->where('company_id', $company)->where('is_active', true))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $company)->where('is_active', true))],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.serials' => ['nullable', 'string'],
        ];
    }
}
