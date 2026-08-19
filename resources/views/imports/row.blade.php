@extends('layouts.app')
@section('title', $editing ? 'Edit Import Row' : 'View Import Row')
@section('content')
<div class="mb-4"><a href="{{ route('imports.show',$batch) }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Import preview</a><h1 class="h3 page-title mt-2">{{ $editing?'Edit':'View' }} Product Row {{ $row->row_number }}</h1><p class="text-muted">{{ $batch->original_filename }}</p></div>

<form method="POST" action="{{ $editing?route('imports.rows.update',[$batch,$row]):'#' }}">
@if($editing)@csrf @method('PUT')@endif
<div class="card">
    <div class="card-body p-4">
        @if($editing)
        <div class="section-surface border rounded-3 p-3 mb-4">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="supplier-lookup"><i class="bi bi-truck me-1 text-primary"></i>Fetch existing supplier</label>
                    <select class="form-select" id="supplier-lookup"><option value="">Select supplier to auto-fill...</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->code }} — {{ $supplier->name }}{{ $supplier->phone ? ' — '.$supplier->phone : '' }}</option>@endforeach</select>
                    <div class="form-text">Selecting a supplier fills code, name and phone.</div>
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="product-lookup"><i class="bi bi-box-seam me-1 text-primary"></i>Fetch existing product</label>
                    <select class="form-select" id="product-lookup"><option value="">Select product to auto-fill...</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->item_code }} — {{ $product->name }}{{ $product->barcode ? ' — '.$product->barcode : '' }}</option>@endforeach</select>
                    <div class="form-text">Selecting a product fills its master details and active prices.</div>
                </div>
            </div>
        </div>
        @endif

        <div class="row g-3">
        @foreach($headers as $header)
            @php
                $value=old($header,$row->data[$header]??'');
                $numberFields=['cost_price','retail_price','wholesale_price','minimum_stock','reorder_level','warranty_months','quantity'];
                $dateFields=['purchase_date','payment_due_date'];
            @endphp
            <div class="col-md-4">
                <label class="form-label" for="field-{{ $header }}">{{ str($header)->headline() }}</label>
                @if($editing && $header==='brand')
                    <select class="form-select" id="field-{{ $header }}" name="{{ $header }}"><option value="">No brand</option>@if($value && !$brands->contains($value))<option value="{{ $value }}" selected>{{ $value }} (from import)</option>@endif @foreach($brands as $option)<option value="{{ $option }}" @selected($value===$option)>{{ $option }}</option>@endforeach</select>
                @elseif($editing && $header==='unit')
                    <select class="form-select" id="field-{{ $header }}" name="{{ $header }}"><option value="">Select unit...</option>@if($value && !$units->pluck('code')->contains($value) && !$units->pluck('name')->contains($value))<option value="{{ $value }}" selected>{{ $value }} (from import)</option>@endif @foreach($units as $option)<option value="{{ $option->code }}" @selected($value===$option->code || $value===$option->name)>{{ $option->code }} — {{ $option->name }}</option>@endforeach</select>
                @elseif($editing && $header==='category')
                    <select class="form-select" id="field-{{ $header }}" name="{{ $header }}"><option value="">Select category...</option>@if($value && !$categories->contains($value))<option value="{{ $value }}" selected>{{ $value }} (from import)</option>@endif @foreach($categories as $option)<option value="{{ $option }}" @selected($value===$option)>{{ $option }}</option>@endforeach</select>
                @elseif($editing && $header==='serial_tracking')
                    <select class="form-select" id="field-{{ $header }}" name="{{ $header }}"><option value="no" @selected(!in_array(strtolower((string)$value),['yes','true','1']))>No</option><option value="yes" @selected(in_array(strtolower((string)$value),['yes','true','1']))>Yes</option></select>
                @else
                    <input class="form-control" id="field-{{ $header }}" name="{{ $header }}" value="{{ $value }}" @if(in_array($header,$dateFields)) type="date" @elseif(in_array($header,$numberFields)) type="number" min="0" step="any" @else type="text" @endif @disabled(!$editing)>
                @endif
            </div>
        @endforeach
        </div>
        @if($row->errors)<div class="alert alert-danger mt-4 mb-0"><strong>Please correct this row:</strong> {{ implode('; ',$row->errors) }}</div>@endif
    </div>
    <div class="card-footer p-3 d-flex gap-2">@if($editing)<button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>@endif<a href="{{ route('imports.show',$batch) }}" class="btn btn-outline-secondary">{{ $editing?'Cancel':'Back' }}</a></div>
</div>
</form>
@endsection

@if($editing)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const suppliers = @json($supplierLookup);
    const products = @json($productLookup);
    const fill = values => Object.entries(values || {}).forEach(([field,value]) => {
        const input = document.getElementById(`field-${field}`);
        if (input && value !== null && value !== undefined) input.value = value;
    });
    document.getElementById('supplier-lookup').addEventListener('change', event => {
        const supplier = suppliers[event.target.value];
        if (supplier) fill({supplier_code:supplier.code,supplier_name:supplier.name,supplier_phone:supplier.phone || ''});
    });
    document.getElementById('product-lookup').addEventListener('change', event => fill(products[event.target.value]));
});
</script>
@endpush
@endif
