@extends('layouts.app')
@section('title', request('payment_type') === 'cash' ? 'Cash Sales' : (request('payment_type') === 'credit' ? 'Credit Sales' : 'All Sales'))
@section('content')
<div class="d-flex justify-content-between align-items-start mb-4"><div><h1 class="h3 page-title mb-1">{{ request('payment_type') === 'cash' ? 'Cash Sales' : (request('payment_type') === 'credit' ? 'Credit Sales' : 'All Sales') }}</h1><p class="text-muted mb-0">Search, filter and open every posted sales invoice.</p></div><div class="d-flex gap-2"><a class="btn btn-success" href="{{ route('sales.cash.create') }}"><i class="bi bi-cart-plus"></i> Cash Sale</a><a class="btn btn-primary" href="{{ route('sales.credit.create') }}"><i class="bi bi-credit-card"></i> Credit Sale</a></div></div>

<div class="row g-3 mb-4">
@foreach([['cash','Cash sales','cash','success'],['credit','Credit sales','credit-card','primary']] as [$type,$label,$icon,$colour])
@php($item=$summary->get($type))
<div class="col-md-6"><a class="card text-decoration-none text-dark h-100 {{ request('payment_type')===$type?'border border-'.$colour:'' }}" href="{{ route('sales.index',['payment_type'=>$type]) }}"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="text-muted">{{ $label }}</div><div class="h4 mb-1">Rs. {{ number_format((float)($item->total??0),2) }}</div><small>{{ (int)($item->invoice_count??0) }} invoices @if($type==='credit') · Rs. {{ number_format((float)($item->balance??0),2) }} outstanding @endif</small></div><div class="metric-icon"><i class="bi bi-{{ $icon }}"></i></div></div></a></div>
@endforeach
</div>

<div class="card"><div class="card-body p-3 border-bottom">
<div class="nav nav-pills mb-3"><a class="nav-link {{ !request('payment_type')?'active':'' }}" href="{{ route('sales.index') }}">All Sales</a><a class="nav-link {{ request('payment_type')==='cash'?'active':'' }}" href="{{ route('sales.index',['payment_type'=>'cash']) }}">Cash Sales</a><a class="nav-link {{ request('payment_type')==='credit'?'active':'' }}" href="{{ route('sales.index',['payment_type'=>'credit']) }}">Credit Sales</a></div>
<form class="row g-2 align-items-end" id="sales-filter-form">
    <div class="col-lg-3"><label class="form-label small">Invoice or customer</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search"></div>
    <div class="col-md-2"><label class="form-label small">Channel</label><select class="form-select" name="channel"><option value="">All channels</option><option value="retail" @selected(request('channel')==='retail')>Retail</option><option value="wholesale" @selected(request('channel')==='wholesale')>Wholesale</option></select></div>
    <div class="col-md-2"><label class="form-label small">Sale type</label><select class="form-select" name="payment_type"><option value="">Cash & credit</option><option value="cash" @selected(request('payment_type')==='cash')>Cash</option><option value="credit" @selected(request('payment_type')==='credit')>Credit</option></select></div>
    <div class="col-md-2"><label class="form-label small">Payment status</label><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['paid','partially_paid','unpaid'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ Str::headline($status) }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small">View sales by</label><select class="form-select" name="date_mode" id="date-mode"><option value="single" @selected($dateMode==='single')>Single date</option><option value="range" @selected($dateMode==='range')>Date range</option></select></div>
    <div class="col-md-3" id="single-date-field"><label class="form-label small">Sales date</label><input class="form-control" type="date" name="date" value="{{ $singleDate }}"></div>
    <div class="col-md-3" id="from-date-field"><label class="form-label small">From</label><input class="form-control" type="date" name="from" value="{{ $dateMode==='range' ? $from : '' }}"></div>
    <div class="col-md-3" id="to-date-field"><label class="form-label small">To</label><input class="form-control" type="date" name="to" value="{{ $dateMode==='range' ? $to : '' }}"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Apply</button></div><div class="col-auto"><a href="{{ route('sales.index') }}" class="btn btn-light">Clear</a></div>
</form></div>
<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Invoice</th><th>Customer</th><th>Date</th><th>Channel</th><th>Sale type</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead><tbody>@forelse($sales as $sale)@php($temporary=$sale->customer->is_walk_in||$sale->customer->code==='WALK-IN')<tr><td class="ps-4"><a class="fw-semibold text-decoration-none" href="{{ route('sales.show',$sale) }}">{{ $sale->document_number }}</a></td><td>{{ $temporary?'Temporary Cash Customer':$sale->customer->name }}</td><td>{{ $sale->sale_date->format('d M Y, h:i A') }}</td><td>{{ Str::headline($sale->channel) }}</td><td><span class="badge {{ $sale->payment_type==='cash'?'text-bg-success':'text-bg-primary' }}">{{ Str::headline($sale->payment_type) }}</span></td><td class="text-end">Rs. {{ number_format($sale->grand_total,2) }}</td><td class="text-end">Rs. {{ number_format($sale->balance_amount,2) }}</td><td><span class="badge text-bg-{{ $sale->payment_status==='paid'?'success':($sale->payment_status==='unpaid'?'danger':'warning') }}">{{ Str::headline($sale->payment_status) }}</span></td><td class="text-end pe-4"><div class="dropdown"><button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">Actions</button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('sales.show',$sale) }}"><i class="bi bi-eye me-2"></i>View / print invoice</a></li>@if(!$temporary&&(float)$sale->balance_amount>0&&$sale->status==='posted')<li><a class="dropdown-item fw-semibold" href="{{ route('receivables.create',['sale_id'=>$sale->id]) }}"><i class="bi bi-cash-coin me-2"></i>Receive payment</a></li>@endif @if(!$temporary)<li><a class="dropdown-item" href="{{ route('receivables.ledger',$sale->customer) }}"><i class="bi bi-journal-text me-2"></i>Customer ledger</a></li>@endif @if($sale->status==='posted')<li><a class="dropdown-item" href="{{ route('sale-returns.create',$sale) }}"><i class="bi bi-arrow-return-left me-2"></i>Return items</a></li>@endif</ul></div></td></tr>@empty<tr><td colspan="9" class="text-center py-5 text-muted">No matching sales found.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $sales->links() }}</div></div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mode = document.getElementById('date-mode');
    const single = document.getElementById('single-date-field');
    const from = document.getElementById('from-date-field');
    const to = document.getElementById('to-date-field');
    const toggleDateFields = () => {
        const isSingle = mode.value === 'single';
        single.classList.toggle('d-none', !isSingle);
        from.classList.toggle('d-none', isSingle);
        to.classList.toggle('d-none', isSingle);
        single.querySelector('input').disabled = !isSingle;
        from.querySelector('input').disabled = isSingle;
        to.querySelector('input').disabled = isSingle;
    };
    mode.addEventListener('change', toggleDateFields);
    toggleDateFields();
});
</script>
@endpush
