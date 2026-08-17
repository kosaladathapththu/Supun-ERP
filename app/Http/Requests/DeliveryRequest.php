<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('sales.create');
    }

    public function rules(): array
    {
        return ['payment_type' => ['required', Rule::in(['cash', 'credit'])], 'due_date' => ['nullable', 'required_if:payment_type,credit', 'date'], 'paid_amount' => ['required', 'numeric', 'min:0'], 'payment_method' => ['required', Rule::in(['cash', 'credit_card', 'debit_card', 'qr', 'cheque', 'bank_transfer', 'mobile_wallet', 'online_payment'])], 'delivery_address' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array'], 'items.*.quantity' => ['nullable', 'numeric', 'min:0']];
    }
}
