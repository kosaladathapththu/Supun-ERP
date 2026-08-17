@extends('layouts.app')
@section('title','Supplier Payables')
@section('content')
<div class="mb-4"><h1 class="h3 page-title mb-1">Supplier Payables</h1><p class="text-muted mb-0">Supplier bills, ledgers, payments and outstanding balances.</p></div>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted mb-1">Total supplier bills</div><div class="h3 mb-0">Rs. {{ number_format($reportTotals['invoiced'],2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-start border-success border-4"><div class="card-body"><div class="text-muted mb-1">Total paid to suppliers</div><div class="h3 mb-0 text-success">Rs. {{ number_format($reportTotals['paid'],2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-start border-danger border-4"><div class="card-body"><div class="text-muted mb-1">Current payable</div><div class="h3 mb-0 text-danger">Rs. {{ number_format($reportTotals['payable'],2) }}</div></div></div></div>
</div>
<div class="card mb-4">
    <div class="card-header bg-white p-3"><div class="d-flex justify-content-between align-items-center gap-3 flex-wrap"><div><div class="fw-semibold">Supplier account summary</div><small class="text-muted">Total billed, amount paid, and the payable still due to each supplier.</small></div><div class="d-flex gap-2 flex-wrap justify-content-end">
        <a href="{{ route('payables.history') }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-clock-history me-1"></i> Bill History</a>
        <a href="{{ route('payables.aging') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-bar-chart me-1"></i> Aging Report</a>
        <a href="{{ route('payables.export.excel') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
        <a href="{{ route('payables.print') }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="{{ route('payables.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> New Payment</a>
    </div></div></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Supplier</th><th>Code</th><th class="text-end">Total billed</th><th class="text-end">Paid amount</th><th class="text-end">Current payable</th><th class="text-end pe-4">Action</th></tr></thead><tbody>
    @forelse($suppliers as $supplier)
        <tr><td class="ps-4 fw-semibold">{{ $supplier->name }}</td><td>{{ $supplier->code }}</td><td class="text-end">Rs. {{ number_format((float)$supplier->total_invoiced,2) }}</td><td class="text-end text-success fw-semibold">Rs. {{ number_format((float)$supplier->total_paid,2) }}</td><td class="text-end fw-semibold {{ (float)$supplier->current_payable>0?'text-danger':'' }}">Rs. {{ number_format((float)$supplier->current_payable,2) }}</td><td class="text-end pe-4"><a class="btn btn-sm btn-outline-primary" href="{{ route('payables.ledger',$supplier) }}"><i class="bi bi-journal-text me-1"></i> View Ledger</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-4">No suppliers available.</td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
