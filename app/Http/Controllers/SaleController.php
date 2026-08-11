<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\{Customer, Product, Sale};
use App\Services\SalePostingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $query = Sale::with('customer')->where('company_id', $companyId);
        if ($search = trim((string) $request->query('search'))) $query->where(fn ($sale) => $sale->where('document_number', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")));
        if ($request->filled('channel')) $query->where('channel', $request->channel);
        if ($request->filled('payment_type')) $query->where('payment_type', $request->payment_type);
        if ($request->filled('status')) $query->where('payment_status', $request->status);
        if ($request->filled('from')) $query->whereDate('sale_date', '>=', $request->from);
        if ($request->filled('to')) $query->whereDate('sale_date', '<=', $request->to);
        $summary = Sale::where('company_id', $companyId)->where('status', 'posted')->selectRaw('payment_type, COUNT(*) invoice_count, SUM(grand_total) total, SUM(balance_amount) balance')->groupBy('payment_type')->get()->keyBy('payment_type');
        return view('sales.index', ['sales' => $query->latest('sale_date')->paginate(20)->withQueryString(), 'summary' => $summary]);
    }

    public function create()
    {
        return view('sales.pos', [
            'customers' => Customer::with('customerType')->where('company_id', auth()->user()->company_id)->where('is_active', 1)->orderByDesc('is_walk_in')->orderBy('name')->get(),
            'products' => Product::with('prices')->where('company_id', auth()->user()->company_id)->where('is_active', 1)->orderByDesc('current_quantity')->orderBy('name')->get(),
        ]);
    }

    public function store(SaleRequest $request, SalePostingService $service)
    {
        $data = $request->validated();
        if ($data['payment_type'] === 'credit') {
            $customer = Customer::where('company_id', $request->user()->company_id)->findOrFail($data['customer_id']);
            if ($customer->is_walk_in) throw ValidationException::withMessages(['customer_id' => 'Credit sales require a registered customer. Create or select a customer first.']);
            if (!$customer->credit_enabled) throw ValidationException::withMessages(['customer_id' => 'Credit is not enabled for this customer. Enable credit in the customer record first.']);
            $data['paid_amount'] = 0;
        }
        $sale = $service->post($data, $request->user());
        return redirect()->route('sales.show', [$sale, 'print' => $request->input('action') === 'print' ? 1 : 0])->with('success', 'Sale posted successfully.');
    }

    public function show(Request $request, $sale)
    {
        $sale = Sale::with(['customer', 'user', 'items.product', 'payments'])->where('company_id', $request->user()->company_id)->findOrFail($sale);
        $company = \Illuminate\Support\Facades\DB::table('companies')->where('id', $sale->company_id)->first();
        return view('sales.show', compact('sale', 'company'));
    }
}
