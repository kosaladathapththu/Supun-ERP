<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerReceiptRequest;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\Sale;
use App\Services\CustomerReceiptService;
use App\Services\ReceivableReportService;
use App\Services\ReceivableXlsxExportService;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function index(Request $r, ReceivableReportService $service)
    {
        $company = $r->user()->company_id;
        $customerId = $r->filled('customer_id') ? $r->integer('customer_id') : null;
        $report = $service->build($company, $customerId);

        return view('receivables.index', ['customers' => $report['customers'], 'reportTotals' => $report['totals']]);
    }

    public function history(Request $r)
    {
        $company = $r->user()->company_id;
        $customerId = $r->filled('customer_id') ? $r->integer('customer_id') : null;
        $allCustomers = Customer::where('company_id', $company)->registered()->where('is_active', 1)->orderBy('name')->get();
        $receipts = CustomerReceipt::with('customer')->where('company_id', $company)->where('status', 'posted')->whereHas('customer', fn ($customer) => $customer->registered())->when($customerId, fn ($q) => $q->where('customer_id', $customerId))->latest('receipt_date')->latest('id')->paginate(20)->withQueryString();

        return view('receivables.history', compact('allCustomers', 'receipts'));
    }

    public function exportExcel(Request $r, ReceivableReportService $reports, ReceivableXlsxExportService $xlsx)
    {
        $customerId = $r->filled('customer_id') ? $r->integer('customer_id') : null;
        $report = $reports->build($r->user()->company_id, $customerId);
        $path = $xlsx->create($report, $r->user()->company_id, $r->user()->name);

        return response()->download($path, 'customer-receivables-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $r, ReceivableReportService $service)
    {
        $customerId = $r->filled('customer_id') ? $r->integer('customer_id') : null;
        $report = $service->build($r->user()->company_id, $customerId);

        return view('receivables.report', compact('report'));
    }

    public function create(Request $r)
    {
        $company = $r->user()->company_id;
        $customers = Customer::where('company_id', $company)->registered()->where('is_active', 1)->orderBy('name')->get();
        $saleId = $r->integer('sale_id');
        $selectedSale = $saleId ? Sale::where('company_id', $company)->whereHas('customer', fn ($customer) => $customer->registered())->where('status', 'posted')->where('balance_amount', '>', 0)->findOrFail($saleId) : null;
        $customerId = $selectedSale?->customer_id ?: $r->integer('customer_id');
        $sales = collect();
        $selectedCustomer = null;
        $receivableSummary = ['invoiced' => 0, 'paid' => 0, 'outstanding' => 0];
        if ($customerId) {
            $selectedCustomer = $customers->firstWhere('id', $customerId);
            abort_unless($selectedCustomer, 404);
            $customerSales = Sale::where('company_id', $company)->where('customer_id', $customerId)->where('status', 'posted')->where('payment_type', 'credit');
            $receivableSummary['invoiced'] = (float) (clone $customerSales)->sum('grand_total');
            $receivableSummary['outstanding'] = (float) (clone $customerSales)->sum('balance_amount');
            $receivableSummary['paid'] = max(0, $receivableSummary['invoiced'] - $receivableSummary['outstanding']);
            $sales = (clone $customerSales)->where('balance_amount', '>', 0)->oldest('due_date')->get();
        }

return view('receivables.create', compact('customers', 'sales', 'customerId', 'saleId', 'selectedSale', 'selectedCustomer', 'receivableSummary'));
    }

    public function store(CustomerReceiptRequest $r, CustomerReceiptService $service)
    {
        $receipt = $service->post($r->validated(), $r->user());

        return redirect()->route('receivables.show', $receipt)->with('success', 'Customer receipt posted successfully.');
    }

    public function show(Request $r, CustomerReceipt $receipt)
    {
        abort_unless($receipt->company_id === $r->user()->company_id, 404);
        $receipt->load(['customer', 'allocations.sale']);

        return view('receivables.show', compact('receipt'));
    }

    public function ledger(Request $r, Customer $customer)
    {
        abort_unless($customer->company_id === $r->user()->company_id && ! $customer->is_walk_in && $customer->code !== 'WALK-IN', 404);
        $sales = Sale::with(['payments' => fn ($query) => $query->where('status', 'posted')->orderBy('payment_date')])
            ->where('customer_id', $customer->id)
            ->where('payment_type', 'credit')
            ->where('status', 'posted')
            ->orderBy('sale_date')
            ->get();
        $invoicePayments = $sales->flatMap->payments;
        $receipts = CustomerReceipt::where('customer_id', $customer->id)->where('status', 'posted')->orderBy('receipt_date')->get();

        return view('receivables.ledger', compact('customer', 'sales', 'invoicePayments', 'receipts'));
    }

    public function aging(Request $r)
    {
        $company = $r->user()->company_id;
        $sales = Sale::with('customer')->where('company_id', $company)->where('status', 'posted')->where('balance_amount', '>', 0)->get();
        $rows = $sales->groupBy('customer_id')->map(function ($items) {
        $b = ['current' => 0, 'days_1_30' => 0, 'days_31_60' => 0, 'days_61_90' => 0, 'over_90' => 0, 'total' => 0];
        foreach ($items as $s) {
        $days = $s->due_date ? now()->startOfDay()->diffInDays($s->due_date, false) * -1 : 0;
        $key = $days <= 0 ? 'current' : ($days <= 30 ? 'days_1_30' : ($days <= 60 ? 'days_31_60' : ($days <= 90 ? 'days_61_90' : 'over_90')));
        $b[$key] += (float) $s->balance_amount;
        $b['total'] += (float) $s->balance_amount;
        }$b['customer'] = $items->first()->customer;

        return $b;
        })->values();

        return view('receivables.aging', compact('rows'));
    }
}
