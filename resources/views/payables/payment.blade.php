@extends('layouts.app')
@section('title','Supplier Payment')
@section('content')
@php
    $supplier = $suppliers->firstWhere('id',(int)$supplierId);
    $totalOutstanding = (float)($position['total_outstanding'] ?? $invoices->sum('balance_amount'));
    $openingOutstanding = (float)($position['opening_outstanding'] ?? 0);
    $defaultAmount = $selectedInvoice ? (float)$selectedInvoice->balance_amount : $totalOutstanding;
@endphp
<div class="d-flex justify-content-between align-items-start mb-4">
    <div><h1 class="h3 page-title mb-1">{{ $selectedInvoice ? 'Pay '.$selectedInvoice->document_number : 'Pay a Supplier' }}</h1><p class="text-muted mb-0">Enter the payment once. The system applies it to outstanding payables automatically.</p></div>
    <a href="{{ route('payables.index') }}" class="btn btn-light">Cancel</a>
</div>

@if(!$supplierId)
<div class="card"><div class="card-body p-4"><h2 class="h5 mb-3">1. Select supplier</h2><form method="GET" class="row g-3"><div class="col-lg-9"><select name="supplier_id" class="form-select form-select-lg" required><option value="">Choose supplier...</option>@foreach($suppliers as $item)<option value="{{ $item->id }}">{{ $item->name }} — {{ $item->code }}</option>@endforeach</select></div><div class="col-lg-3"><button class="btn btn-primary btn-lg w-100">Continue</button></div></form></div></div>
@else
<form method="POST" action="{{ route('payables.store') }}" id="supplier-payment-form">@csrf
    <input type="hidden" name="supplier_id" value="{{ $supplierId }}">
    <input type="hidden" name="invoice_id" value="{{ $invoiceId ?: '' }}">
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card mb-4"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3"><div><div class="text-muted small">SUPPLIER</div><h2 class="h4 mb-0">{{ $supplier?->name }}</h2></div><a href="{{ route('payables.create') }}" class="btn btn-sm btn-outline-secondary">Change</a></div>
                <div class="p-3 rounded bg-light d-flex justify-content-between"><span>Total outstanding</span><strong class="fs-5">Rs. {{ number_format($totalOutstanding,2) }}</strong></div>
            </div></div>
            <div class="card"><div class="card-body p-0"><div class="p-4 pb-2"><h2 class="h5 mb-1">Outstanding payables</h2><p class="small text-muted">Payment is applied to the selected bill first, then other outstanding balances.</p></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Payable</th><th>Date</th><th class="text-end pe-4">Balance</th></tr></thead><tbody>
                @if($openingOutstanding > 0)
                    <tr><td class="ps-4"><strong>Opening payable</strong><div class="small text-muted">Brought-forward supplier balance</div></td><td>{{ optional($supplier?->opening_balance_date)->format('d M Y') ?: '—' }}</td><td class="text-end pe-4 fw-semibold">Rs. {{ number_format($openingOutstanding,2) }}</td></tr>
                @endif
                @foreach($invoices as $invoice)
                    <tr class="{{ $invoiceId===$invoice->id?'table-primary':'' }}"><td class="ps-4"><strong>{{ $invoice->document_number }}</strong><div class="small text-muted">{{ $invoice->supplier_invoice_number ?: 'No supplier reference' }}</div></td><td>{{ optional($invoice->due_date)->format('d M Y') ?: '—' }}</td><td class="text-end pe-4 fw-semibold">Rs. {{ number_format($invoice->balance_amount,2) }}</td></tr>
                @endforeach
                @if($openingOutstanding <= 0 && $invoices->isEmpty())
                    <tr><td colspan="3" class="text-center text-muted py-4">No outstanding payable. A new payment would be saved as a supplier advance.</td></tr>
                @endif
            </tbody></table></div></div>
        </div>
        <div class="col-xl-5"><div class="card position-sticky" style="top:92px"><div class="card-body p-4">
            <h2 class="h5 mb-3">2. Payment details</h2>
            <label class="form-label">Amount to pay</label><div class="input-group input-group-lg mb-2"><span class="input-group-text">Rs.</span><input id="payment-amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount',number_format($defaultAmount,2,'.','')) }}" class="form-control fw-bold" required></div>
            @if($selectedInvoice)<button type="button" class="btn btn-sm btn-link px-0 mb-3" onclick="document.getElementById('payment-amount').value='{{ number_format((float)$selectedInvoice->balance_amount,2,'.','') }}'">Pay full bill: Rs. {{ number_format($selectedInvoice->balance_amount,2) }}</button>@else<button type="button" class="btn btn-sm btn-link px-0 mb-3" onclick="document.getElementById('payment-amount').value='{{ number_format($totalOutstanding,2,'.','') }}'">Pay all outstanding payables</button>@endif
            <div class="row g-3"><div class="col-12"><label class="form-label">Payment date</label><input type="date" name="payment_date" value="{{ old('payment_date',now()->toDateString()) }}" class="form-control" required></div><div class="col-12"><label class="form-label">Payment method</label><select name="payment_method" class="form-select" required>@foreach(['cash'=>'Cash','bank_transfer'=>'Bank transfer','cheque'=>'Cheque','online_payment'=>'Online / QR payment'] as $value=>$label)<option value="{{ $value }}" @selected(old('payment_method')===$value)>{{ $label }}</option>@endforeach</select></div><div class="col-12"><label class="form-label">Reference <span class="text-muted">(optional)</span></label><input name="reference" value="{{ old('reference') }}" class="form-control" placeholder="Cheque number, bank reference, etc."></div></div>
            <div class="alert alert-info mt-4 mb-3 small"><i class="bi bi-info-circle me-1"></i> Partial payments are allowed. Only an amount above the total outstanding payable becomes a supplier advance.</div>
            <button class="btn btn-primary btn-lg w-100"><i class="bi bi-check-circle me-2"></i>Confirm & Post Payment</button>
        </div></div></div>
    </div>
</form>
@endif
@endsection
