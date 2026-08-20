<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::with(['category', 'brand', 'unit'])->where('company_id', $r->user()->company_id);
        if ($s = trim((string) $r->query('search'))) {
            $q->where(fn ($x) => $x->where('item_code', 'like', "%$s%")->orWhere('barcode', 'like', "%$s%")->orWhere('name', 'like', "%$s%"));
        }

return view('products.index', ['products' => $q->latest()->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('products.create', $this->formData(new Product));
    }

    public function store(ProductRequest $r)
    {
        if ($r->has('items')) {
            $items = $r->validated('items');
            $products = DB::transaction(function () use ($items, $r) {
                $created = collect();
                foreach ($items as $item) {
                    $p = Product::create(collect($item)->except(['retail_price', 'wholesale_price'])->merge([
                        'company_id' => $r->user()->company_id,
                        'serial_tracking' => (bool) ($item['serial_tracking'] ?? false),
                        'is_active' => (bool) ($item['is_active'] ?? false),
                    ])->all());
                    $this->pricesFromArray($p, $item, $r->user()->id);
                    $created->push($p);
                }

                return $created;
            });

            $count = count($items);

            if ($r->input('after_save') === 'purchase') {
                return redirect()->route('purchases.direct.create', ['products' => $products->pluck('id')->implode(',')])
                    ->with('success', $count.' product'.($count === 1 ? '' : 's').' created. Complete the supplier bill to post the purchase invoice.');
            }

            return redirect()->route('products.index')->with('success', $count.' product'.($count === 1 ? '' : 's').' created successfully.');
        }

        DB::transaction(function () use ($r) {
        $p = Product::create($this->data($r) + ['company_id' => $r->user()->company_id]);
        $this->prices($p, $r);
        });

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit($product)
    {
        $p = Product::where('company_id', auth()->user()->company_id)->findOrFail($product);

        return view('products.form', $this->formData($p));
    }

    public function update(ProductRequest $r, $product)
    {
        $p = Product::where('company_id', $r->user()->company_id)->findOrFail($product);
        DB::transaction(function () use ($p, $r) {
        $p->update($this->data($r));
        $this->prices($p, $r);
        });

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    private function data($r)
    {
        return array_merge($r->safe()->except(['retail_price', 'wholesale_price']), ['serial_tracking' => $r->boolean('serial_tracking'), 'is_active' => $r->boolean('is_active')]);
    }

    private function prices($p, $r)
    {
        foreach (['retail', 'wholesale'] as $type) {
            $amount = $r->input($type.'_price');
            $current = $p->prices()->where('price_type', $type)->where('is_active', true)->latest('effective_from')->first();
            if (! $current || bccomp((string) $current->amount, (string) $amount, 2) !== 0) {
                if ($current) {
                    $current->update(['is_active' => false, 'effective_until' => now()]);
                }ProductPrice::create(['product_id' => $p->id, 'price_type' => $type, 'amount' => $amount, 'effective_from' => now(), 'is_active' => true, 'created_by' => $r->user()->id]);
            }
        }
    }

    private function pricesFromArray(Product $product, array $item, int $userId): void
    {
        foreach (['retail', 'wholesale'] as $type) {
            ProductPrice::create([
                'product_id' => $product->id,
                'price_type' => $type,
                'amount' => $item[$type.'_price'],
                'effective_from' => now(),
                'is_active' => true,
                'created_by' => $userId,
            ]);
        }
    }

    private function formData($p)
    {
        $cid = auth()->user()->company_id;

        return ['product' => $p, 'categories' => ProductCategory::where('company_id', $cid)->where('is_active', true)->orderBy('name')->get(), 'brands' => Brand::where('company_id', $cid)->where('is_active', true)->orderBy('name')->get(), 'units' => Unit::where('company_id', $cid)->where('is_active', true)->orderBy('name')->get(), 'retailPrice' => $p->prices()->where('price_type', 'retail')->where('is_active', true)->value('amount'), 'wholesalePrice' => $p->prices()->where('price_type', 'wholesale')->where('is_active', true)->value('amount')];
    }
}
