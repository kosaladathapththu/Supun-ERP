<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SalePostingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $query = Sale::with('customer')->where('company_id', $companyId);
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($sale) => $sale->where('document_number', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")));
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('sale_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('sale_date', '<=', $request->to);
        }
        $summary = Sale::where('company_id', $companyId)->where('status', 'posted')->selectRaw('payment_type, COUNT(*) invoice_count, SUM(grand_total) total, SUM(balance_amount) balance')->groupBy('payment_type')->get()->keyBy('payment_type');

        return view('sales.index', ['sales' => $query->latest('sale_date')->paginate(20)->withQueryString(), 'summary' => $summary]);
    }

    public function create(Request $request)
    {
        return view('sales.choose');
    }

    public function cashCreate(Request $request)
    {
        return $this->saleForm($request, 'cash');
    }

    public function creditCreate(Request $request)
    {
        return $this->saleForm($request, 'credit');
    }

    private function saleForm(Request $request, string $saleMode)
    {
        $companyId = $request->user()->company_id;
        $customerQuery = Customer::with('customerType')->where('company_id', $companyId)->where('is_active', 1);
        if ($saleMode === 'credit') {
            $customerQuery->where('is_walk_in', false)->where('credit_enabled', true);
        }

        return view('sales.pos', [
            'customers' => $customerQuery->orderByRaw("CASE WHEN code = 'WALK-IN' THEN 0 ELSE 1 END")->orderBy('name')->get(),
            'products' => Product::with(['prices', 'category'])->where('company_id', $companyId)->where('is_active', 1)->orderByDesc('current_quantity')->orderBy('name')->get(),
            'saleMode' => $saleMode,
        ]);
    }

    public function store(SaleRequest $request, SalePostingService $service)
    {
        $data = $request->validated();
        if ($data['payment_type'] === 'credit') {
            $customer = Customer::where('company_id', $request->user()->company_id)->findOrFail($data['customer_id']);
            if ($customer->is_walk_in) {
                throw ValidationException::withMessages(['customer_id' => 'Credit sales require a registered customer. Create or select a customer first.']);
            }
            if (! $customer->credit_enabled) {
                throw ValidationException::withMessages(['customer_id' => 'Credit is not enabled for this customer. Enable credit in the customer record first.']);
            }
            $data['paid_amount'] = 0;
            $data['walk_in_customer_name'] = null;
        }
        if ($data['payment_type'] === 'cash') {
            $customer = Customer::where('company_id', $request->user()->company_id)->findOrFail($data['customer_id']);
            if ($customer->is_walk_in && $data['channel'] !== 'retail') {
                throw ValidationException::withMessages(['channel' => 'Walk-in sales must use retail pricing.']);
            }
            if (! $customer->is_walk_in) {
                $data['walk_in_customer_name'] = null;
            }
        }
        $sale = $service->post($data, $request->user());
        if (! empty($data['walk_in_customer_name'])) {
            $sale->update(['walk_in_customer_name' => trim($data['walk_in_customer_name'])]);
        }

        return redirect()->route('sales.show', [$sale, 'print' => $request->input('action') === 'print' ? 1 : 0])->with('success', 'Sale posted successfully.');
    }

    public function show(Request $request, $sale)
    {
        $sale = Sale::with(['customer', 'user', 'items.product', 'payments'])->where('company_id', $request->user()->company_id)->findOrFail($sale);
        $company = \Illuminate\Support\Facades\DB::table('companies')->where('id', $sale->company_id)->first();

        return view('sales.show', compact('sale', 'company'));
    }
}
