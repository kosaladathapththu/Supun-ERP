<?php

namespace App\Http\Controllers;

use App\Models\{DebitNote, Supplier, SupplierInvoice, SupplierPayment};
use App\Services\SupplierPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        return view('payables.index', [
            'invoices' => SupplierInvoice::with(['supplier', 'grn'])->where('company_id', $companyId)->latest('invoice_date')->paginate(20),
            'suppliers' => Supplier::where('company_id', $companyId)->where('is_active', 1)->orderBy('name')->get(),
        ]);
    }

    public function print(Request $request)
    {
        $companyId = $request->user()->company_id;
        $invoices = SupplierInvoice::with('supplier')
            ->where('company_id', $companyId)
            ->where('status', 'posted')
            ->orderBy('invoice_date')
            ->orderBy('document_number')
            ->get();

        $totals = [
            'billed' => (float) $invoices->sum('total_amount'),
            'paid' => (float) SupplierPayment::where('company_id', $companyId)->where('status', 'posted')->sum('amount'),
            'credits' => (float) DebitNote::where('company_id', $companyId)->where('status', '!=', 'voided')->sum('amount'),
            'outstanding' => (float) $invoices->sum('balance_amount'),
        ];

        return view('payables.report', compact('invoices', 'totals'));
    }

    public function create(Request $request)
    {
        $companyId = $request->user()->company_id;
        $suppliers = Supplier::where('company_id', $companyId)->where('is_active', 1)->orderBy('name')->get();
        $invoiceId = $request->integer('invoice_id');
        $selectedInvoice = $invoiceId ? SupplierInvoice::where('company_id', $companyId)->where('balance_amount', '>', 0)->findOrFail($invoiceId) : null;
        $supplierId = $selectedInvoice?->supplier_id ?: $request->integer('supplier_id');
        $invoices = $supplierId ? SupplierInvoice::where('company_id', $companyId)->where('supplier_id', $supplierId)->where('balance_amount', '>', 0)->oldest('due_date')->get() : collect();

        return view('payables.payment', compact('suppliers', 'supplierId', 'invoices', 'invoiceId', 'selectedInvoice'));
    }

    public function store(Request $request, SupplierPaymentService $service)
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'invoice_id' => ['nullable', Rule::exists('supplier_invoices', 'id')->where(fn ($query) => $query->where('company_id', $companyId))],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'cheque', 'bank_transfer', 'online_payment'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:150'],
        ]);

        $remaining = (float) $data['amount'];
        $bills = SupplierInvoice::where('company_id', $companyId)
            ->where('supplier_id', $data['supplier_id'])
            ->where('status', 'posted')
            ->where('balance_amount', '>', 0)
            ->when($data['invoice_id'] ?? null, fn ($query, $id) => $query->orderByRaw('id = ? DESC', [$id]))
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();
        $data['allocations'] = [];
        foreach ($bills as $bill) {
            if ($remaining <= 0) break;
            $apply = min($remaining, (float) $bill->balance_amount);
            $data['allocations'][$bill->id] = number_format($apply, 2, '.', '');
            $remaining -= $apply;
        }
        unset($data['invoice_id']);
        $service->post($data, $request->user());

        return redirect()->route('payables.index')->with('success', 'Supplier payment posted and automatically applied to outstanding bills.');
    }

    public function ledger(Request $request, Supplier $supplier)
    {
        abort_unless($supplier->company_id === $request->user()->company_id, 404);
        $invoices = SupplierInvoice::where('supplier_id', $supplier->id)->get();
        $payments = SupplierPayment::where('supplier_id', $supplier->id)->get();
        $debitNotes = DebitNote::where('supplier_id', $supplier->id)->get();

        return view('payables.ledger', compact('supplier', 'invoices', 'payments', 'debitNotes'));
    }

    public function aging(Request $request)
    {
        $items = SupplierInvoice::with('supplier')->where('company_id', $request->user()->company_id)->where('balance_amount', '>', 0)->get();

        return view('payables.aging', compact('items'));
    }
}
