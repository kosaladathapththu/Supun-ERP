<?php
namespace App\Http\Controllers;
use App\Models\Product;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;
class StockController extends Controller
{
 public function index(Request $r){$q=Product::with(['category','brand','unit'])->where('company_id',$r->user()->company_id);if($s=trim((string)$r->query('search')))$q->where(fn($x)=>$x->where('item_code','like',"%$s%")->orWhere('name','like',"%$s%"));return view('stock.index',['products'=>$q->orderBy('name')->paginate(20)->withQueryString(),'costValue'=>(clone $q)->selectRaw('SUM(current_quantity * average_cost) value')->value('value')?:0]);}
 public function ledger(Request $r,$product){$product=Product::where('company_id',$r->user()->company_id)->findOrFail($product);$movements=DB::table('stock_movements')->where('product_id',$product->id)->orderByDesc('movement_at')->orderByDesc('id')->paginate(30);return view('stock.ledger',compact('product','movements'));}
}
