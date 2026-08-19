<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleReturnRequest;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\SaleReturnService;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function index(Request $r)
    {
        $r->validate([
            'search' => 'nullable|string|max:100',
            'payment_type' => 'nullable|in:cash,credit',
            'channel' => 'nullable|in:retail,wholesale',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $company = $r->user()->company_id;
        $sales = Sale::with('customer')
            ->where('company_id', $company)
            ->where('status', 'posted')
            ->when($r->filled('search'), function ($query) use ($r) {
                $search = trim((string) $r->query('search'));
                $query->where(fn ($sale) => $sale
                    ->where('document_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")));
            })
            ->when($r->filled('payment_type'), fn ($query) => $query->where('payment_type', $r->query('payment_type')))
            ->when($r->filled('channel'), fn ($query) => $query->where('channel', $r->query('channel')))
            ->when($r->filled('from'), fn ($query) => $query->whereDate('sale_date', '>=', $r->query('from')))
            ->when($r->filled('to'), fn ($query) => $query->whereDate('sale_date', '<=', $r->query('to')))
            ->latest('sale_date')
            ->limit(100)
            ->get();

        return view('sale-returns.index', [
            'returns' => SaleReturn::with(['sale', 'customer'])->where('company_id', $company)->latest('return_date')->paginate(20)->withQueryString(),
            'sales' => $sales,
        ]);
    }

    public function create(Request $r, $sale)
    {
        $sale = Sale::with(['customer', 'items.product'])->where('company_id', $r->user()->company_id)->findOrFail($sale);
        $returned = SaleReturnItem::selectRaw('sale_item_id,SUM(quantity) quantity')->whereIn('sale_item_id', $sale->items->pluck('id'))->groupBy('sale_item_id')->pluck('quantity', 'sale_item_id');

        return view('sale-returns.create', compact('sale', 'returned'));
    }

    public function store(SaleReturnRequest $r, $sale, SaleReturnService $service)
    {
        $sale = Sale::where('company_id', $r->user()->company_id)->findOrFail($sale);
        $return = $service->post($sale, $r->validated(), $r->user());

        return redirect()->route('sale-returns.show', $return)->with('success', 'Sales return posted successfully.');
    }

    public function show(Request $r, $sale_return)
    {
        $return = SaleReturn::with(['sale', 'customer', 'items.product', 'creditNote'])->where('company_id', $r->user()->company_id)->findOrFail($sale_return);

        return view('sale-returns.show', compact('return'));
    }
}
