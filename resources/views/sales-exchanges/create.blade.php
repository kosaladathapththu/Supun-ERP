@extends('layouts.app')
@section('title','Exchange '.$sale->document_number)
@section('content')
<div class="mb-4"><a href="{{ route('sales.show',$sale) }}">&larr; {{ $sale->document_number }}</a><h1 class="h3 mt-2">Product Exchange</h1><p class="text-muted">Return old items and issue replacement items in one controlled transaction.</p></div>
<form method="POST" action="{{ route('sales-exchanges.store',$sale) }}">@csrf
<div class="card mb-4"><div class="card-body"><h2 class="h5">1. Items returned by customer</h2></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th class="ps-4">Product</th><th>Available to return</th><th>Return now</th><th>Condition</th></tr></thead><tbody>
@foreach($sale->items as $item) @php($available=(float)$item->quantity-(float)($returned[$item->id]??0))
<tr><td class="ps-4">{{ $item->product->name }}</td><td>@quantity($available)</td><td><input class="form-control" type="number" step=".0001" min="0" max="{{ $available }}" name="returned_items[{{ $item->id }}][quantity]" value="0"></td><td><select class="form-select" name="returned_items[{{ $item->id }}][condition]"><option value="resalable">Resalable</option><option value="damaged">Damaged</option></select></td></tr>
@endforeach</tbody></table></div></div>
<div class="card mb-4"><div class="card-body"><div class="d-flex justify-content-between"><div><h2 class="h5">2. Replacement items</h2><p class="small text-muted">The return credit is automatically applied. Any higher replacement value remains due.</p></div><button type="button" class="btn btn-outline-primary" id="add-replacement">Add item</button></div><div id="replacement-lines"></div></div></div>
<div class="card"><div class="card-body"><label class="form-label">Exchange reason</label><textarea class="form-control mb-3" name="reason" required>{{ old('reason') }}</textarea><button class="btn btn-primary" onclick="return confirm('Post this exchange? Stock and accounts will update immediately.')">Post Exchange</button></div></div>
</form>
@push('scripts')<script>
const exchangeProducts=@json($exchangeProducts);
let exchangeLine=0;function addReplacement(){const i=exchangeLine++;const options=exchangeProducts.map(p=>`<option value="${p.id}" data-price="${p.price}">${p.label} (Stock: ${p.stock})</option>`).join('');document.querySelector('#replacement-lines').insertAdjacentHTML('beforeend',`<div class="row g-2 align-items-end border-top pt-3 mt-3 replacement-line"><div class="col-md-6"><label class="form-label">Product</label><select required class="form-select exchange-product" name="replacement_items[${i}][product_id]"><option value="">Select product</option>${options}</select></div><div class="col-md-2"><label class="form-label">Quantity</label><input required class="form-control" type="number" min=".0001" step=".0001" value="1" name="replacement_items[${i}][quantity]"></div><div class="col-md-3"><label class="form-label">Unit price</label><input required class="form-control exchange-price" type="number" min="0" step=".01" name="replacement_items[${i}][unit_price]"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-line">&times;</button></div></div>`) }
document.querySelector('#add-replacement').addEventListener('click',addReplacement);document.querySelector('#replacement-lines').addEventListener('change',e=>{if(e.target.matches('.exchange-product'))e.target.closest('.replacement-line').querySelector('.exchange-price').value=e.target.selectedOptions[0].dataset.price||0});document.querySelector('#replacement-lines').addEventListener('click',e=>{if(e.target.matches('.remove-line'))e.target.closest('.replacement-line').remove()});addReplacement();
</script>@endpush
@endsection

