@extends('layouts.app')
@section('title','Customer Ledger')
@section('content')
@php
    $openingBalance = (float) $customer->opening_balance;
    $totalInvoiced = (float) $sales->sum('grand_total');
    $totalPaid = (float) $receipts->sum('amount');
    $currentReceivable = $openingBalance + $totalInvoiced - $totalPaid;
    $entries = collect();
    if ($openingBalance != 0) {
        $entries->push((object)['date'=>$customer->opening_balance_date,'document'=>'Opening balance','route'=>null,'type'=>'Opening','debit'=>$openingBalance,'credit'=>0,'order'=>0]);
    }
    $entries = $entries
        ->concat($sales->map(fn($sale) => (object)['date'=>$sale->sale_date,'document'=>$sale->document_number,'route'=>route('sales.show',$sale),'type'=>'Invoice','debit'=>(float)$sale->grand_total,'credit'=>0,'order'=>1]))
        ->concat($receipts->map(fn($receipt) => (object)['date'=>$receipt->receipt_date,'document'=>$receipt->receipt_number,'route'=>route('receivables.show',$receipt),'type'=>'Payment received','debit'=>0,'credit'=>(float)$receipt->amount,'order'=>2]))
        ->sortBy(fn($entry) => optional($entry->date)->format('Y-m-d').'-'.$entry->order.'-'.$entry->document)
        ->values();
    $runningBalance = 0;
@endphp

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div><h1 class="h3 page-title mb-1">{{ $customer->name }}</h1><p class="text-muted mb-0">Receivables ledger · {{ $customer->code }}</p></div>
    <a href="{{ route('receivables.create',['customer_id'=>$customer->id]) }}" class="btn btn-primary"><i class="bi bi-cash-coin me-1"></i> Receive Payment</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted mb-1">Total invoiced</div><div class="h3 mb-0">Rs. {{ number_format($openingBalance+$totalInvoiced,2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-start border-success border-4"><div class="card-body"><div class="text-muted mb-1">Total paid by customer</div><div class="h3 mb-0 text-success">Rs. {{ number_format($totalPaid,2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-start border-danger border-4"><div class="card-body"><div class="text-muted mb-1">Current receivable</div><div class="h3 mb-0 {{ $currentReceivable>0?'text-danger':'text-success' }}">Rs. {{ number_format($currentReceivable,2) }}</div></div></div></div>
</div>

<div class="card">
    <div class="card-header bg-white p-3"><div class="fw-semibold">Invoice and payment history</div><small class="text-muted">Payments made by this customer appear in green under Payment received.</small></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Date</th><th>Document</th><th>Type</th><th class="text-end">Invoiced</th><th class="text-end">Paid</th><th class="text-end pe-4">Balance</th></tr></thead><tbody>
    @forelse($entries as $entry)
        @php($runningBalance += $entry->debit - $entry->credit)
        <tr><td class="ps-4">{{ optional($entry->date)->format('d M Y') ?: '—' }}</td><td>@if($entry->route)<a href="{{ $entry->route }}">{{ $entry->document }}</a>@else{{ $entry->document }}@endif</td><td>{{ $entry->type }}</td><td class="text-end">{{ $entry->debit ? number_format($entry->debit,2) : '—' }}</td><td class="text-end text-success fw-semibold">{{ $entry->credit ? number_format($entry->credit,2) : '—' }}</td><td class="text-end pe-4 fw-semibold {{ $runningBalance>0?'text-danger':'' }}">{{ number_format($runningBalance,2) }}</td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-5">No customer transactions recorded.</td></tr>
    @endforelse
    </tbody><tfoot><tr><th colspan="4" class="text-end">Total paid</th><th class="text-end text-success">Rs. {{ number_format($totalPaid,2) }}</th><th></th></tr><tr><th colspan="5" class="text-end">Current receivable</th><th class="text-end pe-4">Rs. {{ number_format($currentReceivable,2) }}</th></tr></tfoot></table></div>
</div>
@endsection
