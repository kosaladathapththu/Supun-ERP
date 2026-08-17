@extends('layouts.app')
@section('title','Customer Payment')

@push('styles')
<style>
    .receipt-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px; }
    .receipt-metric { padding:18px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; }
    .receipt-metric span { display:block; margin-bottom:5px; color:#64748b; }
    .receipt-metric strong { font-size:1.45rem; }
    .receipt-metric.paid { border-left:4px solid #159455; }
    .receipt-metric.paid strong { color:#159455; }
    .receipt-metric.due { border-left:4px solid #e63346; }
    .receipt-metric.due strong { color:#e63346; }
    @media(max-width:760px) { .receipt-summary { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="mb-4">
    <h1 class="h3 page-title mb-1">{{ $selectedSale?'Receive '.$selectedSale->document_number:'New Customer Receipt' }}</h1>
    <p class="text-muted mb-0">Select a customer to see their receivable balance and outstanding invoices.</p>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <form id="customer-loader" method="GET" class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label fw-semibold">Customer</label>
                <select id="receipt-customer" name="customer_id" class="form-select form-select-lg" required>
                    <option value="">Choose customer...</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected($customerId==$customer->id)>{{ $customer->code }} — {{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button id="load-customer" class="btn btn-outline-primary btn-lg w-100">View Receivables</button></div>
        </form>
    </div>
</div>

@if($customerId)
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div><h2 class="h5 fw-bold mb-0">{{ $selectedCustomer->name }}</h2><span class="text-muted">Customer {{ $selectedCustomer->code }}</span></div>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('receivables.ledger',$selectedCustomer) }}"><i class="bi bi-journal-text me-1"></i> View Full Ledger</a>
</div>

<div class="receipt-summary">
    <div class="receipt-metric"><span>Total credit invoiced</span><strong>Rs. {{ number_format($receivableSummary['invoiced'],2) }}</strong></div>
    <div class="receipt-metric paid"><span>Total paid</span><strong>Rs. {{ number_format($receivableSummary['paid'],2) }}</strong></div>
    <div class="receipt-metric due"><span>Current receivable</span><strong>Rs. {{ number_format($receivableSummary['outstanding'],2) }}</strong></div>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('receivables.store') }}">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customerId }}">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Receipt date</label><input type="date" name="receipt_date" value="{{ old('receipt_date',now()->toDateString()) }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Amount received</label><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount',$selectedSale?->balance_amount) }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Payment method</label><select name="payment_method" class="form-select" required>@foreach(['cash','credit_card','debit_card','qr','cheque','bank_transfer','mobile_wallet','online_payment'] as $method)<option value="{{ $method }}" @selected(old('payment_method')===$method)>{{ str($method)->headline() }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Reference</label><input name="reference" value="{{ old('reference') }}" class="form-control"></div>
            </div>

            <div class="d-flex justify-content-between align-items-end mb-2">
                <div><h2 class="h6 fw-bold mb-1">Outstanding invoices</h2><p class="small text-muted mb-0">Enter an amount against an invoice, or leave all lines at zero to apply payment to the oldest invoices first.</p></div>
                <strong class="text-danger">Due: Rs. {{ number_format($receivableSummary['outstanding'],2) }}</strong>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Invoice</th><th>Sale date</th><th>Due date</th><th class="text-end">Outstanding</th><th style="width:190px">Receive now</th></tr></thead>
                    <tbody>
                    @forelse($sales as $sale)
                        <tr class="{{ $saleId===$sale->id?'table-primary':'' }}">
                            <td><a href="{{ route('sales.show',$sale) }}">{{ $sale->document_number }}</a></td>
                            <td>{{ $sale->sale_date->format('d M Y') }}</td>
                            <td>{{ optional($sale->due_date)->format('d M Y') ?: '—' }}</td>
                            <td class="text-end fw-semibold">Rs. {{ number_format($sale->balance_amount,2) }}</td>
                            <td><input type="number" name="allocations[{{ $sale->id }}]" step="0.01" min="0" max="{{ $sale->balance_amount }}" value="{{ old('allocations.'.$sale->id,$saleId===$sale->id?$sale->balance_amount:0) }}" class="form-control form-control-sm allocation"></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>This customer has no outstanding invoices.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="form-check mt-3"><input type="hidden" name="keep_unapplied" value="0"><input class="form-check-input" type="checkbox" name="keep_unapplied" value="1" id="keep-unapplied" @checked(old('keep_unapplied'))><label class="form-check-label" for="keep-unapplied"><strong>Keep payment unallocated</strong> — record it without applying it to an invoice.</label></div>
            <div class="row mt-3"><div class="col-md-8"><label class="form-label">Notes</label><textarea name="notes" class="form-control">{{ old('notes') }}</textarea></div><div class="col-md-4 d-flex align-items-end justify-content-end"><button class="btn btn-primary px-4">Post &amp; Apply Receipt</button></div></div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.querySelector('#receipt-customer')?.addEventListener('change', function () {
    if (!this.value) return;
    const button = document.querySelector('#load-customer');
    button.disabled = true;
    button.textContent = 'Loading...';
    document.querySelector('#customer-loader').requestSubmit();
});
</script>
@endpush
