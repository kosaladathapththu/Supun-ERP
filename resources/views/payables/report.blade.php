@extends('layouts.app')
@section('title','Supplier Payables Report')
@section('content')
@include('reports.partials.professional-header',[
    'reportTitle'=>'Supplier Payables Report',
    'reportPeriod'=>'Posted supplier invoices, payments and balances as at '.now()->format('d F Y'),
    'reportReference'=>'SUPPLIER-PAYABLES-'.now()->format('Ymd'),
    'backUrl'=>route('payables.index')
])
<div class="row g-2 mb-4 report-summary">
    <div class="col-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Total supplier bills</div><strong>Rs. {{ number_format($totals['billed'],2) }}</strong></div></div>
    <div class="col-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Payments made</div><strong class="text-success">Rs. {{ number_format($totals['paid'],2) }}</strong></div></div>
    <div class="col-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Supplier credits</div><strong class="text-info">Rs. {{ number_format($totals['credits'],2) }}</strong></div></div>
    <div class="col-3"><div class="border rounded p-2 h-100"><div class="small text-muted">Outstanding payable</div><strong class="text-danger">Rs. {{ number_format($totals['outstanding'],2) }}</strong></div></div>
</div>
<div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Supplier</th><th>Invoice / Reference</th><th>Date</th><th>Due Date</th><th class="text-end">Total (LKR)</th><th class="text-end">Settled</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($invoices as $invoice)
        @php($settled=max(0,(float)$invoice->total_amount-(float)$invoice->balance_amount))
        <tr><td><strong>{{ $invoice->supplier->name }}</strong><div class="small text-muted">{{ $invoice->supplier->code }}</div></td><td>{{ $invoice->document_number }}<div class="small text-muted">{{ $invoice->supplier_invoice_number ?: '—' }}</div></td><td>{{ $invoice->invoice_date->format('d M Y') }}</td><td>{{ optional($invoice->due_date)->format('d M Y') ?: '—' }}</td><td class="text-end report-money">{{ number_format($invoice->total_amount,2) }}</td><td class="text-end report-money">{{ number_format($settled,2) }}</td><td class="text-end report-money">{{ number_format($invoice->balance_amount,2) }}</td><td>{{ str($invoice->payment_status)->headline() }}</td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-5">No posted supplier invoices.</td></tr>
    @endforelse
    </tbody>
    @if($invoices->isNotEmpty())<tfoot><tr><th colspan="4">Report totals</th><th class="text-end report-money">{{ number_format($totals['billed'],2) }}</th><th class="text-end report-money">{{ number_format($totals['billed']-$totals['outstanding'],2) }}</th><th class="text-end report-money">{{ number_format($totals['outstanding'],2) }}</th><th></th></tr></tfoot>@endif
</table>
</div>
<div class="mt-4 p-3 bg-light border-start border-4 border-primary small"><strong>Report note:</strong> Settled amounts show the portion cleared against each supplier invoice. Supplier credits are disclosed separately in the summary and may differ from cash payments made.</div>
@include('reports.partials.professional-footer')
@endsection
