<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;

class CategoryController extends MasterDataController
{
    protected string $model = ProductCategory::class;

    protected string $route = 'categories';

    protected string $title = 'Category';

    protected array $extraFields = ['parent_id'];

    protected function parents(?int $exclude = null)
    {
        return ProductCategory::where('company_id', auth()->user()->company_id)->when($exclude, fn ($q) => $q->whereKeyNot($exclude))->orderBy('name')->get();
    }
}
