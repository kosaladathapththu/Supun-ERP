<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ProductRequest extends FormRequest
{
 public function authorize():bool{return true;}
 public function rules():array{$id=$this->route('product');$company=$this->user()->company_id;return ['item_code'=>['required','string','max:60',Rule::unique('products')->where(fn($q)=>$q->where('company_id',$company))->ignore($id)],'barcode'=>['nullable','string','max:100',Rule::unique('products')->where(fn($q)=>$q->where('company_id',$company))->ignore($id)],'name'=>['required','string','max:180'],'description'=>['nullable','string'],'model'=>['nullable','string','max:100'],'product_category_id'=>['required',Rule::exists('product_categories','id')->where(fn($q)=>$q->where('company_id',$company))],'brand_id'=>['nullable',Rule::exists('brands','id')->where(fn($q)=>$q->where('company_id',$company))],'unit_id'=>['required',Rule::exists('units','id')->where(fn($q)=>$q->where('company_id',$company))],'average_cost'=>['required','numeric','min:0'],'retail_price'=>['required','numeric','min:0'],'wholesale_price'=>['required','numeric','min:0'],'minimum_stock'=>['required','numeric','min:0'],'reorder_level'=>['required','numeric','min:0'],'rack_location'=>['nullable','string','max:80'],'warranty_months'=>['required','integer','between:0,120'],'serial_tracking'=>['nullable','boolean'],'is_active'=>['nullable','boolean']];}
}
