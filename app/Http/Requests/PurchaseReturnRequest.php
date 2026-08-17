<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:1000'], 'items' => ['required', 'array'], 'items.*.quantity' => ['nullable', 'numeric', 'min:0'], 'items.*.reason_code' => ['nullable', 'string', 'max:30']];
    }
}
