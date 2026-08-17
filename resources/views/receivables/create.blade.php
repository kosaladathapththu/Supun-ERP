@extends('layouts.app')
@section('title','Receive Customer Payment')

@push('styles')
<style>
    .payment-shell { max-width:1100px; margin:auto; }
    .customer-balance { display:flex; justify-content:space-between; align-items:center; gap:18px; padding:20px 24px; border-radius:12px; background:#fff; border-left:5px solid #e63346; box-shadow:0 8px 22px rgba(15,31,56,.06); }
    .customer-balance strong { display:block; color:#e63346; font-size:2rem; line-height:1.15; }
    .payment-fields { display:grid; grid-template-columns:1.1fr 1fr 1fr; gap:16px; }
    .invoice-summary { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
    .invoice-summary-header { display:flex; justify-content:space-between; gap:12px; padding:13px 16px; border-bottom:1px solid #e2e8f0; }
    .invoice-summary table { margin:0; }
    @media(max-width:760px) { .customer-balance { align-items:flex-start; flex-direction:column; } .payment-fields { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="payment-shell">
    <div class="mb-4">
        <h1 class="h3 page-title mb-1">Receive Customer Payment</h1>
        <p class="text-muted mb-0">Choose a customer, enter the amount received, and save the payment.</p>
    </div>

    <div class="card mb-4">
        <div class="card-body p-4">
            <form id="customer-loader" method="GET" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label fw-semibold">1. Select customer</label>
                    <select id="receipt-customer" name="customer_id" class="form-select form-select-lg" required>
                        <option value="">Choose customer...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected($customerId==$customer->id)>{{ $customer->code }} — {{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><button id="load-customer" class="btn btn-outline-primary btn-lg w-100">Show Balance</button></div>
            </form>
        </div>
    </div>

    @if($customerId)
        <div class="customer-balance mb-4">
            <div>
                <span class="text-muted small">SELECTED CUSTOMER</span>
                <h2 class="h5 fw-bold mb-1">{{ $selectedCustomer->name }}</h2>
                <span class="text-muted">{{ $selectedCustomer->code }} · {{ $sales->count() }} unpaid {{ Str::plural('invoice',$sales->count()) }}</span>
            </div>
            <div class="text-md-end">
                <span class="text-muted">Amount currently receivable</span>
                <strong>Rs. {{ number_format($receivableSummary['outstanding'],2) }}</strong>
                <a class="small" href="{{ route('receivables.ledger',$selectedCustomer) }}">View customer ledger</a>
            </div>
        </div>

        @if($receivableSummary['outstanding'] > 0)
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('receivables.store') }}">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customerId }}">
                    <input type="hidden" name="keep_unapplied" value="0">

                    <h2 class="h5 fw-bold mb-3">2. Enter payment received</h2>
                    <div class="payment-fields mb-3">
                        <div>
                            <label class="form-label">Amount received</label>
                            <div class="input-group input-group-lg"><span class="input-group-text">Rs.</span><input type="number" step="0.01" min="0.01" max="{{ $receivableSummary['outstanding'] }}" name="amount" value="{{ old('amount',$selectedSale?->balance_amount) }}" class="form-control" placeholder="0.00" required autofocus></div>
                        </div>
                        <div><label class="form-label">Payment method</label><select name="payment_method" class="form-select form-select-lg" required>@foreach(['cash','credit_card','debit_card','qr','cheque','bank_transfer','mobile_wallet','online_payment'] as $method)<option value="{{ $method }}" @selected(old('payment_method')===$method)>{{ str($method)->headline() }}</option>@endforeach</select></div>
                        <div><label class="form-label">Receipt date</label><input type="date" name="receipt_date" value="{{ old('receipt_date',now()->toDateString()) }}" class="form-control form-control-lg" required></div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label class="form-label">Reference <span class="text-muted">(optional)</span></label><input name="reference" value="{{ old('reference') }}" class="form-control" placeholder="Cheque, bank or transaction number"></div>
                        <div class="col-md-6"><label class="form-label">Note <span class="text-muted">(optional)</span></label><input name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Short payment note"></div>
                    </div>

                    <div class="invoice-summary mb-4">
                        <div class="invoice-summary-header"><div><strong>Outstanding invoices</strong><div class="small text-muted">Payment will be applied automatically to the oldest invoice first.</div></div><span class="badge text-bg-light border align-self-center">{{ $sales->count() }} invoices</span></div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th class="ps-3">Invoice</th><th>Due date</th><th class="text-end pe-3">Balance</th></tr></thead>
                                <tbody>@foreach($sales as $sale)<tr><td class="ps-3"><a href="{{ route('sales.show',$sale) }}">{{ $sale->document_number }}</a></td><td>{{ optional($sale->due_date)->format('d M Y') ?: '—' }}</td><td class="text-end fw-semibold pe-3">Rs. {{ number_format($sale->balance_amount,2) }}</td></tr>@endforeach</tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="small text-muted"><i class="bi bi-shield-check me-1"></i>The system handles invoice allocation automatically.</span>
                        <button class="btn btn-primary btn-lg px-5"><i class="bi bi-check2-circle me-1"></i> Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
        @else
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>This customer has no outstanding receivable. No payment is required.</div>
        @endif
    @endif
</div>
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
