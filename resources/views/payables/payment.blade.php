@extends('layouts.app')
@section('title','Supplier Payment')
@push('styles')
<style>
    .supplier-payment-shell{max-width:1380px;margin:0 auto}.supplier-payment-grid{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(350px,.85fr);gap:24px;align-items:start}.payment-card{border:0;box-shadow:0 14px 35px rgba(25,50,85,.10);overflow:hidden}.payment-card-header{padding:22px 24px;background:linear-gradient(135deg,#173f79,#2867d9);color:#fff}.payment-card-body{padding:24px}.supplier-summary{border-left:5px solid #2563eb}.payable-total{background:#f7f9fc;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px}.payable-total strong{color:#dc3545;font-size:1.45rem}.payment-breakdown{display:grid;grid-template-columns:1fr 1fr;gap:10px}.payment-breakdown>div{background:#f7f9fc;border-radius:10px;padding:12px}.payment-breakdown small{display:block;color:#6b7280}.payment-breakdown strong{display:block;margin-top:3px}.payment-submit{min-height:52px}.payment-help{border-left:4px solid #0dcaf0}.outstanding-table thead th{background:#eef3f9;color:#526176;font-size:.78rem;letter-spacing:.04em;text-transform:uppercase}.selected-payable{background:#edf5ff!important}.supplier-code{font-size:.8rem;color:#6b7280;letter-spacing:.05em}.payment-panel{position:sticky;top:92px}
    @media(max-width:1100px){.supplier-payment-grid{grid-template-columns:1fr}.payment-panel{position:static}}@media(max-width:576px){.payment-card-body{padding:18px}.payment-breakdown{grid-template-columns:1fr}.page-payment-heading{display:block!important}.page-payment-heading .btn{margin-top:14px;width:100%}}
</style>
@endpush
@section('content')
@php
    $supplier = $suppliers->firstWhere('id',(int)$supplierId);
    $totalOutstanding = (float)($position['total_outstanding'] ?? $invoices->sum('balance_amount'));
    $openingOutstanding = (float)($position['opening_outstanding'] ?? 0);
    $invoiceOutstanding = (float)($position['invoice_outstanding'] ?? $invoices->sum('balance_amount'));
    $defaultAmount = $selectedInvoice ? (float)$selectedInvoice->balance_amount : $totalOutstanding;
@endphp
<div class="supplier-payment-shell">
    <div class="d-flex justify-content-between align-items-start mb-4 page-payment-heading">
        <div><h1 class="h3 page-title mb-1">{{ $selectedInvoice ? 'Pay '.$selectedInvoice->document_number : 'Supplier Payment' }}</h1><p class="text-muted mb-0">Enter one payment. The system applies it to outstanding payables automatically.</p></div>
        <a href="{{ route('payables.index') }}" class="btn btn-light"><i class="bi bi-x-lg me-1"></i>Cancel</a>
    </div>

    @if(!$supplierId)
        <div class="card payment-card"><div class="card-body p-4">
            <h2 class="h5 mb-2">Select a supplier</h2><p class="text-muted mb-4">Choose the supplier whose outstanding balance you want to pay.</p>
            <form method="GET" class="row g-3"><div class="col-lg-9"><select name="supplier_id" class="form-select form-select-lg" required><option value="">Choose supplier...</option>@foreach($suppliers as $item)<option value="{{ $item->id }}">{{ $item->name }} — {{ $item->code }}</option>@endforeach</select></div><div class="col-lg-3"><button class="btn btn-primary btn-lg w-100">Continue</button></div></form>
        </div></div>
    @else
        <form method="POST" action="{{ route('payables.store') }}" id="supplier-payment-form">@csrf
            <input type="hidden" name="supplier_id" value="{{ $supplierId }}"><input type="hidden" name="invoice_id" value="{{ $invoiceId ?: '' }}">
            <div class="supplier-payment-grid">
                <main>
                    <div class="card payment-card supplier-summary mb-4"><div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3"><div><div class="text-muted small">PAYING SUPPLIER</div><h2 class="h4 mb-1">{{ $supplier?->name }}</h2><div class="supplier-code">{{ $supplier?->code }}</div></div><div class="d-flex gap-2"><a href="{{ route('payables.ledger',$supplierId) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-journal-text me-1"></i>Ledger</a><a href="{{ route('payables.create') }}" class="btn btn-sm btn-outline-secondary">Change</a></div></div>
                        <div class="payable-total d-flex justify-content-between align-items-center"><span>Current amount payable</span><strong>Rs. {{ number_format($totalOutstanding,2) }}</strong></div>
                    </div></div>
                    <div class="card payment-card"><div class="card-body p-0">
                        <div class="p-4 pb-2"><h2 class="h5 mb-1">Outstanding payables</h2><p class="small text-muted">The selected bill is paid first. Any remaining amount is applied to the oldest payable.</p></div>
                        <div class="table-responsive"><table class="table outstanding-table align-middle mb-0"><thead><tr><th class="ps-4">Payable</th><th>Date</th><th class="text-end pe-4">Balance</th></tr></thead><tbody>
                            @if($openingOutstanding > 0)<tr><td class="ps-4"><strong>Opening payable</strong><div class="small text-muted">Brought-forward supplier balance</div></td><td>{{ optional($supplier?->opening_balance_date)->format('d M Y') ?: '—' }}</td><td class="text-end pe-4 fw-semibold">Rs. {{ number_format($openingOutstanding,2) }}</td></tr>@endif
                            @foreach($invoices as $invoice)<tr class="{{ $invoiceId===$invoice->id?'selected-payable':'' }}"><td class="ps-4"><strong>{{ $invoice->document_number }}</strong><div class="small text-muted">{{ $invoice->supplier_invoice_number ?: 'No supplier reference' }}</div></td><td>{{ optional($invoice->due_date)->format('d M Y') ?: '—' }}</td><td class="text-end pe-4 fw-semibold">Rs. {{ number_format($invoice->balance_amount,2) }}</td></tr>@endforeach
                            @if($openingOutstanding <= 0 && $invoices->isEmpty())<tr><td colspan="3" class="text-center text-muted py-4">No outstanding payable. A new payment would be saved as a supplier advance.</td></tr>@endif
                        </tbody></table></div>
                    </div></div>
                </main>
                <aside class="payment-panel"><div class="card payment-card">
                    <div class="payment-card-header"><div class="small text-white-50 mb-1">PAYMENT DETAILS</div><h2 class="h4 mb-1">Record payment</h2><div class="small text-white-50">Confirm the amount and payment information.</div></div>
                    <div class="payment-card-body">
                        <label class="form-label fw-semibold">Amount to pay</label><div class="input-group input-group-lg mb-2"><span class="input-group-text">Rs.</span><input id="payment-amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount',number_format($defaultAmount,2,'.','')) }}" class="form-control fw-bold" required></div>
                        @if($selectedInvoice)<button type="button" class="btn btn-sm btn-link px-0 mb-3" onclick="document.getElementById('payment-amount').value='{{ number_format((float)$selectedInvoice->balance_amount,2,'.','') }}'">Pay full bill: Rs. {{ number_format($selectedInvoice->balance_amount,2) }}</button>@else<button type="button" class="btn btn-sm btn-link px-0 mb-3" onclick="document.getElementById('payment-amount').value='{{ number_format($totalOutstanding,2,'.','') }}'">Use full outstanding amount</button>@endif
                        <div class="payment-breakdown mb-4"><div><small>Opening payable</small><strong>Rs. {{ number_format($openingOutstanding,2) }}</strong></div><div><small>Invoice payable</small><strong>Rs. {{ number_format($invoiceOutstanding,2) }}</strong></div></div>
                        <div class="row g-3"><div class="col-12"><label class="form-label fw-semibold">Payment date</label><input type="date" name="payment_date" value="{{ old('payment_date',now()->toDateString()) }}" class="form-control" required></div><div class="col-12"><label class="form-label fw-semibold">Payment method</label><select name="payment_method" class="form-select" required>@foreach(['cash'=>'Cash','bank_transfer'=>'Bank transfer','cheque'=>'Cheque','online_payment'=>'Online / QR payment'] as $value=>$label)<option value="{{ $value }}" @selected(old('payment_method')===$value)>{{ $label }}</option>@endforeach</select></div><div class="col-12"><label class="form-label fw-semibold">Reference <span class="text-muted fw-normal">(optional)</span></label><input name="reference" value="{{ old('reference') }}" class="form-control" placeholder="Cheque number, bank reference, etc."></div></div>
                        <div class="alert alert-info payment-help mt-4 mb-3 small"><i class="bi bi-info-circle me-1"></i>Partial payments are allowed. Only an amount above the current payable becomes a supplier advance.</div>
                        <button class="btn btn-primary btn-lg w-100 payment-submit"><i class="bi bi-check-circle me-2"></i>Confirm and Post Payment</button>
                    </div>
                </div></aside>
            </div>
        </form>
    @endif
</div>
@endsection
