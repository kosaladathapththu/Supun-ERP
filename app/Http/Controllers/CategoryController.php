<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends MasterDataController
{
    protected string $model = ProductCategory::class;

    protected string $route = 'categories';

    protected string $title = 'Category';

    protected array $extraFields = ['parent_id'];

    public function quickStore(Request $request): JsonResponse
    {
        $company = $request->user()->company_id;
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('product_categories')->where(fn ($query) => $query->where('company_id', $company))],
            'name' => ['required', 'string', 'max:120'],
        ]);
        $category = ProductCategory::create($data + ['company_id' => $company, 'is_active' => true]);

        return response()->json(['id' => $category->id, 'code' => $category->code, 'name' => $category->name], 201);
    }

    protected function parents(?int $exclude = null)
    {
        return ProductCategory::where('company_id', auth()->user()->company_id)->when($exclude, fn ($q) => $q->whereKeyNot($exclude))->orderBy('name')->get();
    }
}
