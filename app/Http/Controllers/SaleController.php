<?php
namespace App\Http\Controllers;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SalePostingService;
use Illuminate\Http\Request;
class SaleController extends Controller
{
 public function index(Request $r){$q=Sale::with('customer')->where('company_id',$r->user()->company_id);if($s=trim((string)$r->query('search')))$q->where(fn($x)=>$x->where('document_number','like',"%$s%")->orWhereHas('customer',fn($c)=>$c->where('name','like',"%$s%")));if($r->filled('channel'))$q->where('channel',$r->channel);if($r->filled('payment_type'))$q->where('payment_type',$r->payment_type);if($r->filled('status'))$q->where('payment_status',$r->status);if($r->filled('from'))$q->whereDate('sale_date','>=',$r->from);if($r->filled('to'))$q->whereDate('sale_date','<=',$r->to);return view('sales.index',['sales'=>$q->latest('sale_date')->paginate(20)->withQueryString()]);}
 public function create(){return view('sales.pos',['customers'=>Customer::where('company_id',auth()->user()->company_id)->where('is_active',1)->orderByDesc('is_walk_in')->orderBy('name')->get(),'products'=>Product::with('prices')->where('company_id',auth()->user()->company_id)->where('is_active',1)->where('current_quantity','>',0)->orderBy('name')->get()]);}
 public function store(SaleRequest $r,SalePostingService $service){$sale=$service->post($r->validated(),$r->user());return redirect()->route('sales.show',[$sale,'print'=>$r->input('action')==='print'?1:0])->with('success','Sale posted successfully.');}
 public function show(Request $r,$sale){$sale=Sale::with(['customer','items.product','payments'])->where('company_id',$r->user()->company_id)->findOrFail($sale);return view('sales.show',compact('sale'));}
}
