<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $companyId = auth()->user()->company_id;
        $metrics = [
            "Today's sales" => DB::table('sales')->where('company_id', $companyId)->whereDate('sale_date', today())->where('status', 'posted')->sum('grand_total'),
            "Today's gross profit" => DB::table('sales')->where('company_id', $companyId)->whereDate('sale_date', today())->where('status', 'posted')->sum('gross_profit'),
            'Customer outstanding' => DB::table('sales')->where('company_id', $companyId)->where('status', 'posted')->sum('balance_amount'),
            'Products' => DB::table('products')->where('company_id', $companyId)->whereNull('deleted_at')->count(),
            'Customers' => DB::table('customers')->where('company_id', $companyId)->whereNull('deleted_at')->count(),
            'Suppliers' => DB::table('suppliers')->where('company_id', $companyId)->whereNull('deleted_at')->count(),
            'Categories' => DB::table('product_categories')->where('company_id', $companyId)->whereNull('deleted_at')->count(),
            'Low stock items' => DB::table('products')->where('company_id', $companyId)->where('is_active', true)->whereColumn('current_quantity', '<=', 'reorder_level')->count(),
            'Open periods' => DB::table('accounting_periods')->where('status', 'open')->count(),
        ];
        $development = ['percent' => 62, 'phase' => 'Phase 8 of 12', 'label' => 'Returns and credit/debit notes in progress'];
        return view('dashboard', compact('metrics', 'development'));
    }
}
