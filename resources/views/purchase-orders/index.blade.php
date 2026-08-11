@extends('layouts.app')
@section('title','Purchase Orders')
@section('content')
<div class="d-flex justify-content-between mb-4"><div><h1 class="h3 page-title">Purchase Orders</h1><p class="text-muted">Draft, confirm and receive supplier orders.</p></div><a class="btn btn-primary align-self-start" href="{{ route('purchase-orders.create') }}"><i class="bi bi-plus-lg"></i> New PO</a></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th class="ps-4">PO Number</th><th>Supplier</th><th>Date</th><th>Total</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead>
<tbody>@forelse($orders as $order)<tr>
<td class="ps-4"><a href="{{ route('purchase-orders.show',$order) }}" class="fw-semibold text-decoration-none">{{ $order->document_number }}</a></td><td>{{ $order->supplier->name }}</td><td>{{ $order->order_date->format('Y-m-d') }}</td><td>Rs. {{ number_format($order->total_amount,2) }}</td>
<td><span class="badge text-bg-{{ $order->status==='received'?'success':($order->status==='draft'?'secondary':'primary') }}">{{ str_replace('_',' ',Str::headline($order->status)) }}</span></td>
<td class="pe-4 text-end"><div class="dropdown"><button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">Actions</button><ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item" href="{{ route('purchase-orders.show',$order) }}"><i class="bi bi-eye me-2"></i>View order</a></li>
@if(in_array($order->status,['confirmed','partially_received']))<li><a class="dropdown-item" href="{{ route('grn.create',$order) }}"><i class="bi bi-box-arrow-in-down me-2"></i>Receive goods</a></li>@endif
<li><a class="dropdown-item" href="{{ route('payables.ledger',$order->supplier) }}"><i class="bi bi-journal-text me-2"></i>Supplier ledger</a></li>
</ul></div></td></tr>@empty<tr><td colspan="6" class="text-center py-5 text-muted">No purchase orders yet.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $orders->links() }}</div></div>
@endsection
