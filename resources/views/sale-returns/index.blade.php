@extends('layouts.app')
@section('title','Sales Returns')
@section('content')
<div class="mb-4"><h1 class="h3 page-title">Sales Returns</h1><p class="text-muted">Find an invoice and start a full or partial customer return.</p></div>

<div class="card mb-4">
    <div class="card-header px-4 py-3"><h2 class="h5 mb-1">Choose a sales invoice</h2><div class="small text-muted">Filter the invoice list, then select one from the dropdown.</div></div>
    <div class="card-body p-4">
        <form method="GET" action="{{ route('sale-returns.index') }}" class="section-surface border rounded-3 p-3 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4"><label class="form-label">Invoice or customer</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Invoice number or customer name"></div>
                <div class="col-md-4 col-lg-2"><label class="form-label">Sale type</label><select class="form-select" name="payment_type"><option value="">All types</option><option value="cash" @selected(request('payment_type')==='cash')>Cash</option><option value="credit" @selected(request('payment_type')==='credit')>Credit</option></select></div>
                <div class="col-md-4 col-lg-2"><label class="form-label">Channel</label><select class="form-select" name="channel"><option value="">All channels</option><option value="retail" @selected(request('channel')==='retail')>Retail</option><option value="wholesale" @selected(request('channel')==='wholesale')>Wholesale</option></select></div>
                <div class="col-md-4 col-lg-2"><label class="form-label">From date</label><input class="form-control" type="date" name="from" value="{{ request('from') }}"></div>
                <div class="col-md-4 col-lg-2"><label class="form-label">To date</label><input class="form-control" type="date" name="to" value="{{ request('to') }}"></div>
                <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply filters</button></div>
                <div class="col-auto"><a class="btn btn-light border" href="{{ route('sale-returns.index') }}">Clear</a></div>
            </div>
        </form>

        <div class="row g-3 align-items-end">
            <div class="col-lg-9">
                <label class="form-label fw-semibold" for="return-sale-select">Sales invoice</label>
                <select class="form-select form-select-lg" id="return-sale-select" @disabled($sales->isEmpty())>
                    <option value="">{{ $sales->isEmpty() ? 'No matching posted invoices' : 'Select an invoice to return...' }}</option>
                    @foreach($sales as $sale)
                        <option value="{{ route('sale-returns.create', $sale) }}">{{ $sale->document_number }} — {{ $sale->customer->name }} — {{ Str::headline($sale->payment_type) }} — {{ $sale->sale_date->format('d M Y') }} — Rs. {{ number_format($sale->grand_total, 2) }}</option>
                    @endforeach
                </select>
                <div class="form-text">{{ $sales->count() }} matching invoice{{ $sales->count()===1 ? '' : 's' }} found.</div>
            </div>
            <div class="col-lg-3"><button type="button" class="btn btn-success btn-lg w-100" id="start-return" disabled><i class="bi bi-arrow-return-left me-1"></i>Start return</button></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header px-4 py-3"><h2 class="h5 mb-0">Return history</h2></div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th class="ps-4">Return</th><th>Invoice</th><th>Customer</th><th>Date</th><th>Settlement</th><th>Total</th></tr></thead><tbody>@forelse($returns as $x)<tr><td class="ps-4"><a href="{{ route('sale-returns.show',$x) }}">{{ $x->document_number }}</a></td><td><a href="{{ route('sales.show',$x->sale) }}">{{ $x->sale->document_number }}</a></td><td>{{ $x->customer->name }}</td><td>{{ $x->return_date->format('Y-m-d H:i') }}</td><td>{{ Str::headline($x->settlement_type) }}</td><td>Rs. {{ number_format($x->total_amount,2) }}</td></tr>@empty<tr><td colspan="6" class="text-center py-5">No sales returns yet.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $returns->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('return-sale-select');
    const button = document.getElementById('start-return');
    const updateButton = () => button.disabled = !select.value;
    select.addEventListener('change', updateButton);
    button.addEventListener('click', () => { if (select.value) window.location.href = select.value; });
    updateButton();
});
</script>
@endpush
