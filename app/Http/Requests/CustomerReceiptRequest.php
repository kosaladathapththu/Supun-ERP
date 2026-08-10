<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class CustomerReceiptRequest extends FormRequest
{
    public function authorize():bool{return $this->user()->hasPermission('sales.create');}
    public function rules():array
    {
        $company=$this->user()->company_id;
        return [
            'customer_id'=>['required',Rule::exists('customers','id')->where(fn($q)=>$q->where('company_id',$company)->where('is_active',1))],
            'receipt_date'=>['required','date'],'payment_method'=>['required',Rule::in(['cash','credit_card','debit_card','qr','cheque','bank_transfer','mobile_wallet','online_payment'])],
            'amount'=>['required','numeric','gt:0'],'reference'=>['nullable','string','max:150'],'notes'=>['nullable','string','max:1000'],
            'allocations'=>['nullable','array'],'allocations.*'=>['nullable','numeric','min:0'],
        ];
    }
}
