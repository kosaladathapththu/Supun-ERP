@extends('layouts.app')
@section('title','Receivables Aging')
@section('content')
@include('reports.partials.professional-header',[
    'reportTitle'=>'Receivables Aging Report',
    'reportPeriod'=>'Outstanding customer invoices grouped by days past due as at '.now()->format('d F Y'),
    'reportReference'=>'RECEIVABLES-AGING-'.now()->format('Ymd'),
    'backUrl'=>route('receivables.index')
])
@php
    $agingTotals=[];
    foreach(['current','days_1_30','days_31_60','days_61_90','over_90','total'] as $key) $agingTotals[$key]=$rows->sum($key);
@endphp
<div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Customer</th><th class="text-end">Current</th><th class="text-end">1–30 Days</th><th class="text-end">31–60 Days</th><th class="text-end">61–90 Days</th><th class="text-end">Over 90</th><th class="text-end">Total (LKR)</th></tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr><td><a href="{{ route('receivables.ledger',$row['customer']) }}">{{ $row['customer']->name }}</a><div class="small text-muted">{{ $row['customer']->code }}</div></td>@foreach(['current','days_1_30','days_31_60','days_61_90','over_90','total'] as $key)<td class="text-end report-money">{{ number_format($row[$key],2) }}</td>@endforeach</tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-5">No outstanding receivables.</td></tr>
    @endforelse
    </tbody>
    @if($rows->isNotEmpty())<tfoot><tr><th>Total receivables</th>@foreach(['current','days_1_30','days_31_60','days_61_90','over_90','total'] as $key)<th class="text-end report-money">{{ number_format($agingTotals[$key],2) }}</th>@endforeach</tr></tfoot>@endif
</table>
</div>
<div class="mt-4 p-3 bg-light border-start border-4 border-primary small"><strong>Report note:</strong> Current balances are not yet overdue. The remaining columns show amounts outstanding according to the number of days past their payment due dates.</div>
@include('reports.partials.professional-footer')
@endsection
