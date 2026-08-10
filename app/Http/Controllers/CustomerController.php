<?php
namespace App\Http\Controllers;
use App\Models\Customer;
class CustomerController extends PartyController { protected string $model=Customer::class; protected string $route='customers'; protected string $title='Customer'; }
