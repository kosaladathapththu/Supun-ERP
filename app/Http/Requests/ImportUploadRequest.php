<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('imports.create');
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx']];
    }

    public function messages(): array
    {
        return ['file.mimes' => 'Upload an Excel .xlsx workbook or CSV file using the provided columns.'];
    }
}
