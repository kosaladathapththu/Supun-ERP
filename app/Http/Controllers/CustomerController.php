<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\{CustomerReceipt,Sale};
use App\Services\ReceivableReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CustomerController extends PartyController {
 protected string $model=Customer::class; protected string $route='customers'; protected string $title='Customer';
 public function show(Request $request,$record,ReceivableReportService $reports){$customer=Customer::where('company_id',$request->user()->company_id)->registered()->findOrFail($record);$report=$reports->build($request->user()->company_id,$customer->id);$summary=$report['selectedCustomer'];$sales=Sale::where('company_id',$request->user()->company_id)->where('customer_id',$customer->id)->where('status','posted')->latest('sale_date')->limit(15)->get();$receipts=CustomerReceipt::where('company_id',$request->user()->company_id)->where('customer_id',$customer->id)->where('status','posted')->latest('receipt_date')->limit(15)->get();$activity=['quotations'=>DB::table('quotations')->where('customer_id',$customer->id)->count(),'orders'=>DB::table('sales_orders')->where('customer_id',$customer->id)->count(),'deliveries'=>DB::table('delivery_notes')->where('customer_id',$customer->id)->count(),'returns'=>DB::table('sale_returns')->where('customer_id',$customer->id)->count()];return view('customers.show',compact('customer','summary','sales','receipts','activity'));}
 public function print(Request $request,$record,ReceivableReportService $reports){$company=$request->user()->company_id;$customer=Customer::where('company_id',$company)->registered()->findOrFail($record);$report=$reports->build($company,$customer->id);$summary=$report['selectedCustomer'];$sales=Sale::where('company_id',$company)->where('customer_id',$customer->id)->where('status','posted')->latest('sale_date')->limit(5)->get();$receipts=CustomerReceipt::where('company_id',$company)->where('customer_id',$customer->id)->where('status','posted')->latest('receipt_date')->limit(5)->get();return view('customers.print',compact('customer','summary','sales','receipts'));}
}
