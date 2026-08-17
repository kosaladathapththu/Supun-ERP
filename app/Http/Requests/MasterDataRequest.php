<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $table = match ($this->route()->getName()) {
            'categories.store', 'categories.update' => 'product_categories',
            'brands.store', 'brands.update' => 'brands',
            default => 'units',
        };
        $recordId = is_object($this->route('record')) ? $this->route('record')->id : $this->route('record');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique($table)->where(fn ($q) => $q->where('company_id', $this->user()->company_id))->ignore($recordId)],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'decimal_places' => [$table === 'units' ? 'required' : 'nullable', 'integer', 'between:0,4'],
            'parent_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where(fn ($q) => $q->where('company_id', $this->user()->company_id))],
        ];
    }
}
