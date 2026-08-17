@extends('layouts.app')
@section('title','Receipt History')
@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
    <div>
        <a href="{{ route('receivables.index') }}" class="text-decoration-none d-inline-block mb-2"><i class="bi bi-arrow-left me-1"></i> Customer receivables</a>
        <h1 class="h3 page-title mb-1">Receipt History</h1>
        <p class="text-muted mb-0">Review and filter all payments received from registered customers.</p>
    </div>
    <a href="{{ route('receivables.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Receipt</a>
</div>

<div class="card">
    <div class="card-header bg-white p-3">
        <form class="row g-2 align-items-center" method="GET" action="{{ route('receivables.history') }}">
            <div class="col-md-5">
                <select name="customer_id" class="form-select">
                    <option value="">All customers</option>
                    @foreach($allCustomers as $customer)
                        <option value="{{ $customer->id }}" @selected(request('customer_id')==$customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel me-1"></i> Filter</button></div>
            @if(request()->filled('customer_id'))<div class="col-auto"><a href="{{ route('receivables.history') }}" class="btn btn-link text-decoration-none">Clear</a></div>@endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th class="ps-4">Receipt</th><th>Date</th><th>Customer</th><th>Method</th><th class="text-end">Payment received</th><th class="text-end pe-4">Applied to invoices</th></tr></thead>
            <tbody>
            @forelse($receipts as $receipt)
                <tr><td class="ps-4"><a href="{{ route('receivables.show',$receipt) }}">{{ $receipt->receipt_number }}</a></td><td>{{ $receipt->receipt_date->format('d M Y') }}</td><td>{{ $receipt->customer->name }}</td><td>{{ str($receipt->payment_method)->headline() }}</td><td class="text-end">Rs. {{ number_format($receipt->amount,2) }}</td><td class="text-end pe-4">Rs. {{ number_format($receipt->allocated_amount,2) }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No customer receipts recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($receipts->hasPages())<div class="card-footer bg-white">{{ $receipts->links() }}</div>@endif
</div>
@endsection
