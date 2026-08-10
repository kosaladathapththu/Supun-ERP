<?php
namespace App\Http\Controllers;
use App\Models\Unit;
class UnitController extends MasterDataController { protected string $model=Unit::class; protected string $route='units'; protected string $title='Unit'; protected array $extraFields=['decimal_places']; }
