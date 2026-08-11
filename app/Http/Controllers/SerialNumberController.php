<?php

namespace App\Http\Controllers;

use App\Models\{Product, ProductSerialNumber, SaleItem, StockLocation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class SerialNumberController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $query = ProductSerialNumber::with(['product', 'stockLocation', 'goodsReceivedNoteItem.goodsReceivedNote.supplier', 'saleItems.sale.customer'])
            ->whereHas('product', fn ($q) => $q->where('company_id', $companyId));
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('serial_number', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($p) => $p->where('item_code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('product_id')) $query->where('product_id', $request->integer('product_id'));
        if ($request->boolean('expiring')) $query->whereBetween('warranty_expires_on', [now()->toDateString(), now()->addDays(30)->toDateString()]);

        return view('serial-numbers.index', [
            'serials' => $query->latest()->paginate(25)->withQueryString(),
            'products' => Product::where('company_id', $companyId)->where('serial_tracking', true)->orderBy('name')->get(),
            'locations' => StockLocation::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate(['product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)], 'stock_location_id' => ['nullable', Rule::exists('stock_locations', 'id')->where('company_id', $companyId)], 'serials' => 'required|string|max:20000']);
        $serials = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $data['serials'])))));
        if (!$serials) throw ValidationException::withMessages(['serials' => 'Enter at least one serial number.']);
        $existing = ProductSerialNumber::whereIn('serial_number', $serials)->pluck('serial_number')->all();
        if ($existing) throw ValidationException::withMessages(['serials' => 'Already registered: '.implode(', ', array_slice($existing, 0, 10))]);
        foreach ($serials as $serial) ProductSerialNumber::create(['product_id' => $data['product_id'], 'stock_location_id' => $data['stock_location_id'] ?? null, 'serial_number' => $serial, 'status' => 'available']);
        return back()->with('success', count($serials).' serial number(s) registered.');
    }

    public function show(Request $request, ProductSerialNumber $serialNumber)
    {
        $this->authorizeCompany($request, $serialNumber);
        $serialNumber->load(['product', 'stockLocation', 'goodsReceivedNoteItem.goodsReceivedNote.supplier', 'saleItems.sale.customer']);
        $saleItems = SaleItem::with(['sale.customer'])->where('product_id', $serialNumber->product_id)->whereHas('sale', fn ($q) => $q->where('company_id', $request->user()->company_id)->where('status', 'posted'))->latest()->limit(100)->get();
        return view('serial-numbers.show', compact('serialNumber', 'saleItems'));
    }

    public function update(Request $request, ProductSerialNumber $serialNumber)
    {
        $this->authorizeCompany($request, $serialNumber);
        $data = $request->validate(['status' => ['required', Rule::in(['available', 'sold', 'returned', 'damaged', 'service'])], 'sale_item_id' => 'nullable|integer', 'warranty_starts_on' => 'nullable|date', 'warranty_expires_on' => 'nullable|date|after_or_equal:warranty_starts_on']);
        DB::transaction(function () use ($data, $serialNumber, $request) {
            DB::table('sale_item_serials')->where('product_serial_number_id', $serialNumber->id)->delete();
            if (!empty($data['sale_item_id'])) {
                $item = SaleItem::where('id', $data['sale_item_id'])->where('product_id', $serialNumber->product_id)->whereHas('sale', fn ($q) => $q->where('company_id', $request->user()->company_id))->firstOrFail();
                DB::table('sale_item_serials')->insert(['sale_item_id' => $item->id, 'product_serial_number_id' => $serialNumber->id]);
                $start = $data['warranty_starts_on'] ?: $item->sale->sale_date->toDateString();
                $data['warranty_starts_on'] = $start;
                $data['warranty_expires_on'] = $data['warranty_expires_on'] ?: Carbon::parse($start)->addMonths($serialNumber->product->warranty_months)->toDateString();
                $data['status'] = 'sold';
            }
            unset($data['sale_item_id']);
            $serialNumber->update($data);
        });
        return redirect()->route('serial-numbers.show', $serialNumber)->with('success', 'Serial and warranty record updated.');
    }

    private function authorizeCompany(Request $request, ProductSerialNumber $serialNumber): void
    {
        abort_unless($serialNumber->product()->where('company_id', $request->user()->company_id)->exists(), 404);
    }
}
