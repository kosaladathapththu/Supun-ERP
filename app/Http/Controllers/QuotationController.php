<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuotationRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\SalesDocumentWorkflowService;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(Request $r)
    {
        return view('quotations.index', ['quotations' => Quotation::with('customer')->where('company_id', $r->user()->company_id)->latest('quotation_date')->paginate(20)]);
    }

    public function create(Request $r)
    {
        $company = $r->user()->company_id;

        return view('quotations.create', ['customers' => Customer::where('company_id', $company)->registered()->where('is_active', 1)->orderBy('name')->get(), 'products' => Product::with('prices')->where('company_id', $company)->where('is_active', 1)->orderBy('name')->get()]);
    }

    public function store(QuotationRequest $r, SalesDocumentWorkflowService $s)
    {
        $q = $s->createQuotation($r->validated(), $r->user());

        return redirect()->route('quotations.show', $q)->with('success', 'Quotation created.');
    }

    public function show(Request $r, $quotation)
    {
        $quotation = Quotation::with(['customer', 'items.product', 'salesOrder'])->where('company_id', $r->user()->company_id)->findOrFail($quotation);

        return view('quotations.show', compact('quotation'));
    }

    public function convert(Request $r, $quotation, SalesDocumentWorkflowService $s)
    {
        $quotation = Quotation::where('company_id', $r->user()->company_id)->findOrFail($quotation);
        $order = $s->convertToOrder($quotation, $r->user());

        return redirect()->route('sales-orders.show', $order)->with('success', 'Quotation converted to sales order.');
    }
}
