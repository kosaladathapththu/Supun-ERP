@extends('layouts.app')
@section('title','Supplier Payables')
@section('content')
<div class="mb-4">
    <h1 class="h3 page-title mb-1">Supplier Payables</h1>
    <p class="text-muted mb-0">Supplier bills, ledgers, payments and outstanding balances.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted mb-1">Total supplier bills</div><div class="h3 mb-0">Rs. {{ number_format($reportTotals['invoiced'],2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-start border-success border-4"><div class="card-body"><div class="text-muted mb-1">Total paid to suppliers</div><div class="h3 mb-0 text-success">Rs. {{ number_format($reportTotals['paid'],2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-start border-danger border-4"><div class="card-body"><div class="text-muted mb-1">Current payable</div><div class="h3 mb-0 text-danger">Rs. {{ number_format($reportTotals['payable'],2) }}</div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white p-3">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <div class="fw-semibold">Supplier account summary</div>
                <small class="text-muted">Total billed, amount paid, and the payable still due to each supplier.</small>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                <a href="#supplier-bills" class="btn btn-sm btn-outline-dark"><i class="bi bi-clock-history me-1"></i> Bill History</a>
                <a href="{{ route('payables.aging') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-bar-chart me-1"></i> Aging Report</a>
                <a href="{{ route('payables.print') }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
                <a href="{{ route('payables.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> New Payment</a>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th class="ps-4">Supplier</th><th>Code</th><th class="text-end">Total billed</th><th class="text-end">Paid amount</th><th class="text-end">Current payable</th><th class="text-end pe-4">Action</th></tr></thead>
            <tbody>
            @forelse($suppliers as $supplier)
                <tr><td class="ps-4 fw-semibold">{{ $supplier->name }}</td><td>{{ $supplier->code }}</td><td class="text-end">Rs. {{ number_format((float)$supplier->total_invoiced,2) }}</td><td class="text-end text-success fw-semibold">Rs. {{ number_format((float)$supplier->total_paid,2) }}</td><td class="text-end fw-semibold {{ (float)$supplier->current_payable>0?'text-danger':'' }}">Rs. {{ number_format((float)$supplier->current_payable,2) }}</td><td class="text-end pe-4"><a class="btn btn-sm btn-outline-primary" href="{{ route('payables.ledger',$supplier) }}"><i class="bi bi-journal-text me-1"></i> View Ledger</a></td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No suppliers available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" id="supplier-bills">
    <div class="card-header bg-white p-3"><div class="fw-semibold">Supplier bill history</div><small class="text-muted">Pay a bill directly, inspect its source GRN, or open the supplier ledger.</small></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Invoice</th><th>Supplier</th><th>Date</th><th>Due</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead><tbody>
    @forelse($invoices as $invoice)
        <tr><td class="ps-4"><strong>{{ $invoice->document_number }}</strong><div class="small text-muted">Supplier ref: {{ $invoice->supplier_invoice_number ?: '—' }}</div></td><td>{{ $invoice->supplier->name }}</td><td>{{ $invoice->invoice_date->format('d M Y') }}</td><td class="{{ $invoice->due_date&&$invoice->due_date->isPast()&&(float)$invoice->balance_amount>0?'text-danger fw-semibold':'' }}">{{ optional($invoice->due_date)->format('d M Y') ?: '—' }}</td><td class="text-end">{{ number_format($invoice->total_amount,2) }}</td><td class="text-end fw-semibold">{{ number_format($invoice->balance_amount,2) }}</td><td><span class="badge text-bg-{{ $invoice->payment_status==='paid'?'success':($invoice->payment_status==='unpaid'?'danger':'warning') }}">{{ str($invoice->payment_status)->headline() }}</span></td><td class="text-end pe-4"><div class="dropdown"><button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">Actions</button><ul class="dropdown-menu dropdown-menu-end">@if((float)$invoice->balance_amount>0)<li><a class="dropdown-item fw-semibold" href="{{ route('payables.create',['invoice_id'=>$invoice->id]) }}"><i class="bi bi-wallet2 me-2"></i>Pay this bill</a></li>@endif<li><a class="dropdown-item" href="{{ route('payables.ledger',$invoice->supplier) }}"><i class="bi bi-journal-text me-2"></i>Supplier ledger</a></li>@if($invoice->grn)<li><a class="dropdown-item" href="{{ route('grn.show',$invoice->grn) }}"><i class="bi bi-box-arrow-in-down me-2"></i>Source GRN</a></li>@endif</ul></div></td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-5">No supplier invoices yet.</td></tr>
    @endforelse
    </tbody></table></div><div class="p-3">{{ $invoices->links() }}</div>
</div>
@endsection
