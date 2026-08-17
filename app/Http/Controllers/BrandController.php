<?php

namespace App\Http\Controllers;

use App\Models\Brand;

class BrandController extends MasterDataController
{
    protected string $model = Brand::class;

    protected string $route = 'brands';

    protected string $title = 'Brand';
}
