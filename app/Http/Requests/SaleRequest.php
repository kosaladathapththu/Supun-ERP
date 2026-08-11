<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('sales.create');
    }

    public function rules(): array
    {
        $company = $this->user()->company_id;

        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->where(fn ($query) => $query->where('company_id', $company)->where('is_active', 1))],
            'walk_in_customer_name' => ['nullable', 'string', 'max:150'],
            'channel' => ['required', Rule::in(['retail', 'wholesale'])],
            'payment_type' => ['required', Rule::in(['cash', 'credit'])],
            'due_date' => ['nullable', 'required_if:payment_type,credit', 'date'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'required_if:payment_type,cash', Rule::in(['cash', 'credit_card', 'debit_card', 'qr', 'cheque', 'bank_transfer', 'mobile_wallet', 'online_payment'])],
            'payment_reference' => ['nullable', 'string', 'max:150'],
            'cheque_number' => ['nullable', 'required_if:payment_method,cheque', 'string', 'max:100'],
            'cheque_bank' => ['nullable', 'required_if:payment_method,cheque', 'string', 'max:150'],
            'cheque_date' => ['nullable', 'required_if:payment_method,cheque', 'date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where(fn ($query) => $query->where('company_id', $company)->where('is_active', 1))],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
