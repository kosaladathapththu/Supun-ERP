<?php
namespace App\Http\Requests;use Illuminate\Foundation\Http\FormRequest;use Illuminate\Validation\Rule;
class SaleReturnRequest extends FormRequest{public function authorize():bool{return $this->user()->hasPermission('sales.create');}public function rules():array{return ['settlement_type'=>['required',Rule::in(['credit_note','cash_refund'])],'reason'=>['required','string','max:1000'],'items'=>['required','array'],'items.*.quantity'=>['nullable','numeric','min:0'],'items.*.condition'=>['nullable',Rule::in(['resalable','damaged'])]];}}
