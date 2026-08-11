<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between"><h2 class="h5">Products</h2><button type="button" id="add-item" class="btn btn-sm btn-outline-primary">Add row</button></div>
    <table class="table"><thead><tr><th>Product</th><th style="width:220px">{{ $signed ? 'Quantity change' : 'Quantity' }}</th><th style="width:60px"></th></tr></thead><tbody id="item-rows"></tbody></table>
</div></div>
@push('scripts')
<script>
let inventoryRow = 0;
const inventorySelectedProduct = @json($selectedProductId ?? 0);
const inventoryProducts = @json($products->map(fn ($product) => ['id' => $product->id, 'name' => $product->item_code.' — '.$product->name, 'qty' => $product->current_quantity]));
function addInventoryRow(preselected = 0) {
    const index = inventoryRow++;
    const options = inventoryProducts.map(product => `<option value="${product.id}" ${Number(product.id) === Number(preselected) ? 'selected' : ''}>${product.name} (company stock ${product.qty})</option>`).join('');
    document.getElementById('item-rows').insertAdjacentHTML('beforeend', `<tr><td><select name="items[${index}][product_id]" class="form-select" required><option value="">Select product</option>${options}</select></td><td><input type="number" step="0.0001" {{ $signed ? '' : 'min="0"' }} name="items[${index}][{{ $field }}]" class="form-control" value="0"></td><td><button type="button" class="btn btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td></tr>`);
}
document.getElementById('add-item').onclick = () => addInventoryRow();
addInventoryRow(inventorySelectedProduct);
</script>
@endpush
