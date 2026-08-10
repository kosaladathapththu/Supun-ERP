<?php
namespace App\Http\Controllers;
use App\Models\Supplier;
class SupplierController extends PartyController { protected string $model=Supplier::class; protected string $route='suppliers'; protected string $title='Supplier'; }
