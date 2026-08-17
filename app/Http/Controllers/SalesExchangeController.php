<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturnItem;
use App\Models\SalesExchange;
use App\Services\SalesExchangeService;
use Illuminate\Http\Request;

class SalesExchangeController extends Controller
{
    public function create(Request $request, $sale)
    {
        $sale = Sale::with(['customer', 'items.product'])->where('company_id', $request->user()->company_id)->where('status', 'posted')->findOrFail($sale);
        $returned = SaleReturnItem::selectRaw('sale_item_id,SUM(quantity) quantity')->whereIn('sale_item_id', $sale->items->pluck('id'))->groupBy('sale_item_id')->pluck('quantity', 'sale_item_id');
        $products = Product::with('prices')->where('company_id', $request->user()->company_id)->where('is_active', 1)->where('current_quantity', '>', 0)->orderBy('name')->get();
        $exchangeProducts = $products->map(function ($product) use ($sale) {
            $channelPrice = $product->prices->firstWhere('price_type', $sale->channel);
            $fallbackPrice = $product->prices->first();

            return ['id' => $product->id, 'label' => $product->item_code.' — '.$product->name,
                'price' => (float) ($channelPrice->selling_price ?? $fallbackPrice->selling_price ?? 0),
                'stock' => (float) $product->current_quantity];
        })->values();

        return view('sales-exchanges.create', compact('sale', 'returned', 'products', 'exchangeProducts'));
    }

    public function store(Request $request, $sale, SalesExchangeService $service)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:1000',
            'returned_items' => 'required|array',
            'returned_items.*.quantity' => 'nullable|numeric|min:0',
            'returned_items.*.condition' => 'required|in:resalable,damaged',
            'replacement_items' => 'required|array|min:1',
            'replacement_items.*.product_id' => 'required|integer|distinct',
            'replacement_items.*.quantity' => 'required|numeric|gt:0',
            'replacement_items.*.unit_price' => 'required|numeric|min:0',
        ]);
        $exchange = $service->post(Sale::findOrFail($sale), $data, $request->user());

        return redirect()->route('sales-exchanges.show', $exchange)->with('success', 'Exchange posted successfully.');
    }

    public function show(Request $request, $exchange)
    {
        $exchange = SalesExchange::with(['originalSale.customer', 'replacementSale', 'saleReturn', 'creditNote'])->where('company_id', $request->user()->company_id)->findOrFail($exchange);

        return view('sales-exchanges.show', compact('exchange'));
    }

    public function void(Request $request, $sale, SalesExchangeService $service)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        $return = $service->void(Sale::findOrFail($sale), $data['reason'], $request->user());

        return redirect()->route('sales.show', $sale)->with('success', 'Invoice voided with reversal '.$return->document_number.'.');
    }
}
