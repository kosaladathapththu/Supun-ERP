<?php
namespace App\Services;
use App\Models\{CustomerReceipt,Sale};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CustomerReceiptService
{
    public function post(array $data,$user):CustomerReceipt
    {
        return DB::transaction(function()use($data,$user){
            $amount=(string)$data['amount'];$allocated='0';$lines=[];
            $allocations=$data['allocations']??[];
            $hasManualAllocation=collect($allocations)->contains(fn($value)=>$value&&bccomp((string)$value,'0',2)>0);
            if(!$hasManualAllocation&&!($data['keep_unapplied']??false)){
                $remaining=$amount;
                $openSales=Sale::where('company_id',$user->company_id)->where('customer_id',$data['customer_id'])
                    ->where('status','posted')->where('payment_type','credit')->where('balance_amount','>',0)
                    ->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderBy('sale_date')->lockForUpdate()->get();
                foreach($openSales as $sale){
                    if(bccomp($remaining,'0',2)<=0)break;
                    $value=bccomp($remaining,(string)$sale->balance_amount,2)>0?(string)$sale->balance_amount:$remaining;
                    $allocations[$sale->id]=$value;$remaining=bcsub($remaining,$value,2);
                }
            }
            foreach($allocations as $saleId=>$value){
                if(!$value||bccomp((string)$value,'0',2)<=0)continue;
                $sale=Sale::where('company_id',$user->company_id)->where('customer_id',$data['customer_id'])->where('status','posted')->lockForUpdate()->findOrFail($saleId);
                if(bccomp((string)$value,(string)$sale->balance_amount,2)>0)throw ValidationException::withMessages(['allocations'=>"Allocation exceeds the balance of {$sale->document_number}."]);
                $allocated=bcadd($allocated,(string)$value,2);$lines[]=[$sale,(string)$value];
            }
            if(bccomp($allocated,$amount,2)>0)throw ValidationException::withMessages(['allocations'=>'Total allocation cannot exceed the receipt amount.']);
            $receipt=CustomerReceipt::create(['company_id'=>$user->company_id,'customer_id'=>$data['customer_id'],'received_by'=>$user->id,'receipt_number'=>app(DocumentNumberService::class)->next($user->company_id,'customer_receipt','REC'),'receipt_date'=>$data['receipt_date'],'payment_method'=>$data['payment_method'],'amount'=>$amount,'allocated_amount'=>$allocated,'unapplied_amount'=>bcsub($amount,$allocated,2),'reference'=>$data['reference']??null,'notes'=>$data['notes']??null]);
            foreach($lines as [$sale,$value]){$receipt->allocations()->create(['sale_id'=>$sale->id,'amount'=>$value]);$paid=bcadd((string)$sale->paid_amount,$value,2);$balance=bcsub((string)$sale->grand_total,$paid,2);$sale->update(['paid_amount'=>$paid,'balance_amount'=>$balance,'payment_status'=>bccomp($balance,'0',2)<=0?'paid':'partially_paid']);}
            $journal=[['account_code'=>$data['payment_method']==='cash'?'1110':'1120','debit'=>$amount]];if(bccomp($allocated,'0',2)>0)$journal[]=['account_code'=>'1130','credit'=>$allocated,'customer_id'=>$data['customer_id']];$advance=bcsub($amount,$allocated,2);if(bccomp($advance,'0',2)>0)$journal[]=['account_code'=>'2150','credit'=>$advance,'customer_id'=>$data['customer_id']];app(JournalPostingService::class)->post($user->company_id,$data['receipt_date'],CustomerReceipt::class,$receipt->id,$receipt->receipt_number,"Customer receipt {$receipt->receipt_number}",$journal,$user->id);
            return $receipt;
        });
    }
}
