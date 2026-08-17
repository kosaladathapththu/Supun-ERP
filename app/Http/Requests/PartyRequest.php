<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = str_starts_with($this->route()->getName(), 'customers.');
        $table = $customer ? 'customers' : 'suppliers';
        $id = $this->route('record');

        return ['code' => ['required', 'string', 'max:50', Rule::unique($table)->where(fn ($q) => $q->where('company_id', $this->user()->company_id))->ignore($id)], 'name' => ['required', 'string', 'max:150'], 'business_name' => ['nullable', 'string', 'max:150'], 'contact_person' => ['nullable', 'string', 'max:150'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'address' => ['nullable', 'string', 'max:1000'], 'credit_enabled' => ['nullable', 'boolean'], 'default_due_term' => ['nullable', Rule::in(['7_days', '14_days', '30_days', 'end_of_month', 'custom'])], 'default_credit_days' => ['nullable', 'integer', 'between:0,365'], 'is_active' => ['nullable', 'boolean']];
    }
}
