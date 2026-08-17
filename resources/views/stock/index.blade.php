@extends('layouts.app')
@section('title','Current Stock')
@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 page-title">Current Stock</h1>
        <p class="text-muted">Cached quantities reconciled from immutable stock movements.</p>
    </div>
    <div class="d-flex align-items-stretch gap-2 flex-wrap justify-content-end">
        <a href="{{ route('imports.index') }}" class="btn btn-primary d-flex align-items-center">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Add Bulk Inventory
        </a>
        <div class="card">
            <div class="card-body py-2 px-4">
                <small class="text-muted">Total cost value</small>
                <div class="fw-bold">Rs. {{ number_format($costValue,2) }}</div>
            </div>
        </div>
    </div>
</div>
<div class="card"><div class="p-3 border-bottom"><form class="d-flex gap-2"><input class="form-control" style="max-width:420px" name="search" value="{{ request('search') }}" placeholder="Search item code or product"><button class="btn btn-outline-secondary">Search</button></form></div><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th class="ps-4">Product</th><th>Category</th><th>On Hand</th><th>Average Cost</th><th>Stock Value</th><th>Reorder</th><th class="text-end pe-4">Actions</th></tr></thead><tbody>
@forelse($products as $product)<tr><td class="ps-4"><strong>{{ $product->name }}</strong><div class="small text-muted">{{ $product->item_code }}</div></td><td>{{ $product->category->name }}</td><td class="fw-semibold">@quantity($product->current_quantity) {{ $product->unit->code }}</td><td>Rs. {{ number_format($product->average_cost,4) }}</td><td>Rs. @quantity($product->current_quantity*$product->average_cost)</td><td>@if($product->current_quantity<=$product->reorder_level)<span class="badge text-bg-warning">Low stock</span>@else<span class="badge text-bg-success">OK</span>@endif</td>
<td class="pe-4 text-end"><div class="dropdown"><button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">Actions</button><ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item" href="{{ route('stock.ledger',$product) }}"><i class="bi bi-journal-text me-2"></i>Stock ledger</a></li>
<li><a class="dropdown-item" href="{{ route('inventory-operations.transfer',['product_id'=>$product->id]) }}"><i class="bi bi-arrow-left-right me-2"></i>Transfer stock</a></li>
<li><a class="dropdown-item" href="{{ route('inventory-operations.adjustment',['product_id'=>$product->id]) }}"><i class="bi bi-sliders me-2"></i>Adjust stock</a></li>
<li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="{{ route('products.edit',$product) }}"><i class="bi bi-pencil me-2"></i>Edit product</a></li>
</ul></div></td></tr>@empty<tr><td colspan="7" class="text-center py-5 text-muted">No products available.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $products->links() }}</div></div>
@endsection
