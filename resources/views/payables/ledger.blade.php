@extends('layouts.app')
@section('title','Supplier Ledger')
@section('content')
@php
    $entries = collect()
        ->concat($invoices->map(fn($x) => (object)['date'=>$x->invoice_date,'document'=>$x->document_number,'type'=>'Supplier invoice','debit'=>0,'credit'=>(float)$x->total_amount,'order'=>1]))
        ->concat($debitNotes->map(fn($x) => (object)['date'=>$x->note_date,'document'=>$x->document_number,'type'=>'Purchase return / debit note','debit'=>(float)$x->amount,'credit'=>0,'order'=>2]))
        ->concat($payments->map(fn($x) => (object)['date'=>$x->payment_date,'document'=>$x->payment_number,'type'=>'Supplier payment','debit'=>(float)$x->amount,'credit'=>0,'order'=>3]))
        ->sortBy(fn($x) => $x->date->format('Y-m-d').'-'.$x->order.'-'.$x->document)
        ->values();
    $running = 0;
@endphp
<div class="d-flex justify-content-between align-items-start mb-4">
    <div><a href="{{ route('payables.index') }}" class="text-decoration-none">&larr; Supplier payables</a><h1 class="h3 page-title mt-2">{{ $supplier->name }} Ledger</h1><p class="text-muted mb-0">Bills increase the balance; payments and debit notes reduce it.</p></div>
    <a href="{{ route('payables.create',['supplier_id'=>$supplier->id]) }}" class="btn btn-primary">Pay Supplier</a>
</div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Date</th><th>Document</th><th>Type</th><th class="text-end">Increase</th><th class="text-end">Decrease</th><th class="text-end pe-4">Balance</th></tr></thead><tbody>
@forelse($entries as $x)
    @php($running += $x->credit - $x->debit)
    <tr><td class="ps-4">{{ $x->date->format('d M Y') }}</td><td>{{ $x->document }}</td><td>{{ $x->type }}</td><td class="text-end">{{ $x->credit ? number_format($x->credit,2) : '—' }}</td><td class="text-end">{{ $x->debit ? number_format($x->debit,2) : '—' }}</td><td class="text-end pe-4 fw-semibold">{{ number_format($running,2) }}</td></tr>
@empty
    <tr><td colspan="6" class="text-center text-muted py-5">No supplier transactions recorded.</td></tr>
@endforelse
</tbody><tfoot><tr><th colspan="5" class="text-end">Current supplier balance</th><th class="text-end pe-4">Rs. {{ number_format($running,2) }}</th></tr></tfoot></table></div></div>
@endsection
