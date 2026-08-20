<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('items')) {
            $items = collect($this->input('items', []))->map(function ($item) {
                $item['warranty_months'] = filled($item['warranty_months'] ?? null) ? $item['warranty_months'] : 0;
                return $item;
            })->all();
            $this->merge(['items' => $items]);
        } elseif (! filled($this->input('warranty_months'))) {
            $this->merge(['warranty_months' => 0]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product');
        $company = $this->user()->company_id;

        if ($this->has('items')) {
            $category = Rule::exists('product_categories', 'id')->where(fn ($q) => $q->where('company_id', $company));
            $brand = Rule::exists('brands', 'id')->where(fn ($q) => $q->where('company_id', $company));
            $unit = Rule::exists('units', 'id')->where(fn ($q) => $q->where('company_id', $company));

            return [
                'items' => ['required', 'array', 'min:1', 'max:100'],
                'items.*.item_code' => ['required', 'string', 'max:60', 'distinct:strict', Rule::unique('products')->where(fn ($q) => $q->where('company_id', $company))],
                'items.*.barcode' => ['nullable', 'string', 'max:100', 'distinct:strict', Rule::unique('products')->where(fn ($q) => $q->where('company_id', $company))],
                'items.*.name' => ['required', 'string', 'max:180'],
                'items.*.product_category_id' => ['required', $category],
                'items.*.brand_id' => ['nullable', $brand],
                'items.*.unit_id' => ['required', $unit],
                'items.*.model' => ['nullable', 'string', 'max:100'],
                'items.*.average_cost' => ['required', 'numeric', 'min:0'],
                'items.*.retail_price' => ['required', 'numeric', 'min:0'],
                'items.*.wholesale_price' => ['required', 'numeric', 'min:0'],
                'items.*.minimum_stock' => ['required', 'numeric', 'min:0'],
                'items.*.reorder_level' => ['required', 'numeric', 'min:0'],
                'items.*.rack_location' => ['nullable', 'string', 'max:80'],
                'items.*.warranty_months' => ['nullable', 'integer', 'between:0,120'],
                'items.*.serial_tracking' => ['nullable', 'boolean'],
                'items.*.is_active' => ['nullable', 'boolean'],
            ];
        }

        return ['item_code' => ['required', 'string', 'max:60', Rule::unique('products')->where(fn ($q) => $q->where('company_id', $company))->ignore($id)], 'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products')->where(fn ($q) => $q->where('company_id', $company))->ignore($id)], 'name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'model' => ['nullable', 'string', 'max:100'], 'product_category_id' => ['required', Rule::exists('product_categories', 'id')->where(fn ($q) => $q->where('company_id', $company))], 'brand_id' => ['nullable', Rule::exists('brands', 'id')->where(fn ($q) => $q->where('company_id', $company))], 'unit_id' => ['required', Rule::exists('units', 'id')->where(fn ($q) => $q->where('company_id', $company))], 'average_cost' => ['required', 'numeric', 'min:0'], 'retail_price' => ['required', 'numeric', 'min:0'], 'wholesale_price' => ['required', 'numeric', 'min:0'], 'minimum_stock' => ['required', 'numeric', 'min:0'], 'reorder_level' => ['required', 'numeric', 'min:0'], 'rack_location' => ['nullable', 'string', 'max:80'], 'warranty_months' => ['required', 'integer', 'between:0,120'], 'serial_tracking' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean']];
    }
}
