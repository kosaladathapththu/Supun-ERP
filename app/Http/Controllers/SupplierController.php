<?php

namespace App\Http\Controllers;

use App\Models\DebitNote;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierController extends PartyController
{
    protected string $model = Supplier::class;

    protected string $route = 'suppliers';

    protected string $title = 'Supplier';

    public function show(Request $request, $record)
    {
        $supplier = Supplier::where('company_id', $request->user()->company_id)->findOrFail($record);
        $invoiceQuery = SupplierInvoice::where('company_id', $supplier->company_id)->where('supplier_id', $supplier->id)->where('status', 'posted');
        $invoices = (clone $invoiceQuery)->latest('invoice_date')->limit(10)->get();
        $totalBilled = (float) (clone $invoiceQuery)->sum('total_amount');
        $totalPaid = (float) SupplierPayment::where('company_id', $supplier->company_id)->where('supplier_id', $supplier->id)->where('status', 'posted')->sum('amount');
        $totalCredits = (float) DebitNote::where('company_id', $supplier->company_id)->where('supplier_id', $supplier->id)->where('status', '!=', 'voided')->sum('amount');
        $currentPayable = max(0, (float) $supplier->opening_balance + $totalBilled - $totalPaid - $totalCredits);

        return view('suppliers.show', compact('supplier', 'invoices', 'totalBilled', 'totalPaid', 'totalCredits', 'currentPayable'));
    }
}
