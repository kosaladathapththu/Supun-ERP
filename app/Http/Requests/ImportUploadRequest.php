<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ImportUploadRequest extends FormRequest
{
 public function authorize():bool{return $this->user()->hasPermission('imports.create');}
 public function rules():array{return ['file'=>['required','file','max:10240','mimes:csv,txt']];}
 public function messages():array{return ['file.mimes'=>'Upload the provided CSV template. Excel .xlsx support requires enabling PHP GD first.'];}
}
