<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseReturnRequest;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index(Request $r)
    {
        return view('purchase-returns.index', ['returns' => PurchaseReturn::with(['supplier', 'grn'])->where('company_id', $r->user()->company_id)->latest('return_date')->paginate(20), 'grns' => GoodsReceivedNote::with('supplier')->where('company_id', $r->user()->company_id)->where('status', 'posted')->latest('received_date')->limit(25)->get()]);
    }

    public function create(Request $r, $grn)
    {
        $grn = GoodsReceivedNote::with(['supplier', 'items.product'])->where('company_id', $r->user()->company_id)->findOrFail($grn);
        $returned = DB::table('purchase_return_items')->selectRaw('goods_received_note_item_id, SUM(quantity) quantity')->whereIn('goods_received_note_item_id', $grn->items->pluck('id'))->groupBy('goods_received_note_item_id')->pluck('quantity', 'goods_received_note_item_id');

        return view('purchase-returns.create', compact('grn', 'returned'));
    }

    public function store(PurchaseReturnRequest $r, $grn, PurchaseReturnService $service)
    {
        $grn = GoodsReceivedNote::where('company_id', $r->user()->company_id)->findOrFail($grn);
        $return = $service->post($grn, $r->validated(), $r->user());

        return redirect()->route('purchase-returns.show', $return)->with('success', 'Purchase return, debit note, inventory and accounting entries posted.');
    }

    public function show(Request $r, $purchase_return)
    {
        $return = PurchaseReturn::with(['supplier', 'grn', 'items.product', 'debitNote'])->where('company_id', $r->user()->company_id)->findOrFail($purchase_return);

        return view('purchase-returns.show', compact('return'));
    }
}
