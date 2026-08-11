<?php

namespace App\Http\Controllers;

use App\Models\{Product, StockAdjustment, StockCount, StockLocation, StockTransfer};
use App\Services\AdvancedInventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryOperationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        return view('inventory-operations.index', [
            'transfers' => StockTransfer::with(['fromLocation', 'toLocation'])->where('company_id', $companyId)->latest()->limit(10)->get(),
            'adjustments' => StockAdjustment::with('location')->where('company_id', $companyId)->latest()->limit(10)->get(),
            'counts' => StockCount::with('location')->where('company_id', $companyId)->latest()->limit(10)->get(),
        ]);
    }

    private function data(Request $request): array
    {
        $companyId = $request->user()->company_id;

        return [
            'locations' => StockLocation::where('company_id', $companyId)->where('is_active', 1)->get(),
            'products' => Product::where('company_id', $companyId)->where('is_active', 1)->orderBy('name')->get(),
            'selectedProductId' => $request->integer('product_id'),
        ];
    }

    public function transferForm(Request $request) { return view('inventory-operations.transfer', $this->data($request)); }

    public function transfer(Request $request, AdvancedInventoryService $service)
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate([
            'from_location_id' => ['required', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'to_location_id' => ['required', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'notes' => 'nullable|string|max:1000', 'items' => 'required|array',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'items.*.quantity' => 'nullable|numeric|min:0',
        ]);
        $service->transfer($data, $request->user());
        return redirect()->route('inventory-operations.index')->with('success', 'Stock transfer posted.');
    }

    public function adjustmentForm(Request $request) { return view('inventory-operations.adjustment', $this->data($request)); }

    public function adjustment(Request $request, AdvancedInventoryService $service)
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate([
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'adjustment_type' => 'required|in:correction,damaged,opening', 'reason' => 'required|string|max:1000', 'items' => 'required|array',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'items.*.quantity_change' => 'nullable|numeric',
        ]);
        $service->adjust($data, $request->user());
        return redirect()->route('inventory-operations.index')->with('success', 'Stock adjustment and accounting entry posted.');
    }

    public function countForm(Request $request) { return view('inventory-operations.count-start', $this->data($request)); }

    public function startCount(Request $request, AdvancedInventoryService $service)
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate([
            'stock_location_id' => ['required', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'notes' => 'nullable|string|max:1000',
        ]);
        $count = $service->startCount($data['stock_location_id'], $data['notes'] ?? null, $request->user());
        return redirect()->route('inventory-operations.count.edit', $count);
    }

    public function editCount(Request $request, $count)
    {
        $count = StockCount::with(['location', 'items.product'])->where('company_id', $request->user()->company_id)->findOrFail($count);
        return view('inventory-operations.count', compact('count'));
    }

    public function postCount(Request $request, $count, AdvancedInventoryService $service)
    {
        $count = StockCount::where('company_id', $request->user()->company_id)->findOrFail($count);
        $data = $request->validate(['items' => 'required|array', 'items.*' => 'required|numeric|min:0']);
        $service->postCount($count, $data['items'], $request->user());
        return redirect()->route('inventory-operations.index')->with('success', 'Stock count posted and variances reconciled.');
    }
}
