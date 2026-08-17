@extends('layouts.app')
@section('title','Customer Receivables Report')
@section('content')
@include('reports.partials.professional-header',[
    'reportTitle'=>'Customer Receivables Report',
    'reportPeriod'=>$report['selectedCustomer']?'Customer: '.$report['selectedCustomer']->name.' ('.$report['selectedCustomer']->code.')':'All registered customers as at '.now()->format('d M Y'),
    'reportReference'=>'RECEIVABLES-'.now()->format('Ymd-His'),
    'backUrl'=>route('receivables.index',request()->only('customer_id')),
])

<div class="row g-3 mb-4 report-money">
@foreach([['Total credit sales',$report['totals']['invoiced'],'#17396b'],['Total paid by customers',$report['totals']['received'],'#17894c'],['Current receivable',$report['totals']['current_receivable'],'#d92d3a']] as [$label,$value,$colour])
<div class="col-4"><div style="border:1px solid #dfe5ee;border-top:4px solid {{ $colour }};padding:14px;background:#f8fafc"><div class="text-muted small text-uppercase">{{ $label }}</div><div class="h4 mb-0 mt-1" style="color:{{ $colour }}">Rs. {{ number_format($value,2) }}</div></div></div>
@endforeach
</div>

<h2 class="h5 mt-4">Customer account summary</h2>
<table class="table report-money">
<thead><tr><th>Code</th><th>Customer</th><th class="text-end">Total invoiced</th><th class="text-end">Paid amount</th><th class="text-end">Invoice outstanding</th><th class="text-end">Paid - not allocated</th><th class="text-end">Current receivable</th></tr></thead>
<tbody>@forelse($report['customers'] as $customer)<tr><td>{{ $customer->code }}</td><td>{{ $customer->name }}</td><td class="text-end">{{ number_format((float)$customer->total_invoiced,2) }}</td><td class="text-end">{{ number_format((float)$customer->total_received,2) }}</td><td class="text-end">{{ number_format((float)$customer->outstanding_balance,2) }}</td><td class="text-end">{{ number_format((float)$customer->available_advance,2) }}</td><td class="text-end fw-semibold">{{ number_format((float)$customer->current_receivable,2) }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No customer balances available.</td></tr>@endforelse</tbody>
<tfoot><tr><th colspan="2">Total</th><th class="text-end">{{ number_format($report['totals']['invoiced'],2) }}</th><th class="text-end">{{ number_format($report['totals']['received'],2) }}</th><th class="text-end">{{ number_format($report['totals']['outstanding'],2) }}</th><th class="text-end">{{ number_format($report['totals']['advances'],2) }}</th><th class="text-end">{{ number_format($report['totals']['current_receivable'],2) }}</th></tr></tfoot>
</table>

<h2 class="h5 mt-4">Posted receipt history</h2>
<table class="table report-money">
<thead><tr><th>Receipt</th><th>Date</th><th>Customer</th><th>Method</th><th>Reference</th><th class="text-end">Payment received</th><th class="text-end">Applied to invoices</th></tr></thead>
<tbody>@forelse($report['receipts'] as $receipt)<tr><td>{{ $receipt->receipt_number }}</td><td>{{ $receipt->receipt_date->format('d M Y') }}</td><td>{{ $receipt->customer->name }}</td><td>{{ str($receipt->payment_method)->headline() }}</td><td>{{ $receipt->reference ?: '-' }}</td><td class="text-end">{{ number_format((float)$receipt->amount,2) }}</td><td class="text-end">{{ number_format((float)$receipt->allocated_amount,2) }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No posted receipts for this report scope.</td></tr>@endforelse</tbody>
@if($report['receipts']->isNotEmpty())<tfoot><tr><th colspan="5">Receipt totals</th><th class="text-end">{{ number_format((float)$report['receipts']->sum('amount'),2) }}</th><th class="text-end">{{ number_format((float)$report['receipts']->sum('allocated_amount'),2) }}</th></tr></tfoot>@endif
</table>

@include('reports.partials.professional-footer')
@endsection
