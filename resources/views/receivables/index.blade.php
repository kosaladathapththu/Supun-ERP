@extends('layouts.app')
@section('title','Customer Receivables')
@section('content')
<div class="mb-4">
    <h1 class="h3 page-title mb-1">Customer Receivables</h1>
    <p class="text-muted mb-0">Credit invoices, customer ledgers, payments and outstanding balances.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted mb-1">Total credit sales</div><div class="h3 mb-0">Rs. {{ number_format($reportTotals['invoiced'],2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-start border-success border-4"><div class="card-body"><div class="text-muted mb-1">Total paid by customers</div><div class="h3 mb-0 text-success">Rs. {{ number_format($reportTotals['received'],2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-start border-danger border-4"><div class="card-body"><div class="text-muted mb-1">Current receivable</div><div class="h3 mb-0 text-danger">Rs. {{ number_format($reportTotals['current_receivable'],2) }}</div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white p-3">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <div class="fw-semibold">Customer account summary</div>
                <small class="text-muted">Total invoiced, amount paid against invoices, and the receivable still due from each customer.</small>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                <a href="{{ route('receivables.history') }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-clock-history me-1"></i> Receipt History</a>
                <a href="{{ route('sales.index',['payment_type'=>'credit']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-receipt-cutoff me-1"></i> Credit Sales</a>
                <a href="{{ route('receivables.aging') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-bar-chart me-1"></i> Aging Report</a>
                <a href="{{ route('receivables.export.excel') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
                <a href="{{ route('receivables.export.pdf') }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
                <a href="{{ route('receivables.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> New Receipt</a>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th class="ps-4">Customer</th><th>Code</th><th class="text-end">Total invoiced</th><th class="text-end">Paid amount</th><th class="text-end">Current receivable</th><th class="text-end pe-4">Action</th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr><td class="ps-4 fw-semibold">{{ $customer->name }}</td><td>{{ $customer->code }}</td><td class="text-end">Rs. {{ number_format((float)$customer->total_invoiced,2) }}</td><td class="text-end text-success fw-semibold">Rs. {{ number_format((float)$customer->total_received,2) }}</td><td class="text-end fw-semibold {{ (float)$customer->current_receivable>0?'text-danger':'' }}">Rs. {{ number_format((float)$customer->current_receivable,2) }}</td><td class="text-end pe-4"><a class="btn btn-sm btn-outline-primary" href="{{ route('receivables.ledger',$customer) }}"><i class="bi bi-journal-text me-1"></i> View Ledger</a></td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No customers available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
