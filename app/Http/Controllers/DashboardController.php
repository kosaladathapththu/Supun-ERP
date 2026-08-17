<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $companyId = auth()->user()->company_id;
        $sales = DB::table('sales')->where('company_id', $companyId)->where('status', 'posted');
        $receivable = (float) (clone $sales)->sum('balance_amount');
        $payable = (float) DB::table('supplier_invoices')->where('company_id', $companyId)->sum('balance_amount');
        $customerPayments = (float) (clone $sales)->where('payment_type', 'credit')->sum('paid_amount');

        $metrics = [
            "Today's sales" => ['value' => (float) (clone $sales)->whereDate('sale_date', today())->sum('grand_total'), 'money' => true, 'icon' => 'cart-check', 'route' => route('sales.index')],
            "Today's gross profit" => ['value' => (float) (clone $sales)->whereDate('sale_date', today())->sum('gross_profit'), 'money' => true, 'icon' => 'graph-up-arrow', 'route' => route('reports.profitability')],
            'Customer receivables' => ['value' => $receivable, 'money' => true, 'icon' => 'cash-coin', 'route' => route('receivables.index')],
            'Credit sales paid amount' => ['value' => $customerPayments, 'money' => true, 'icon' => 'cash-stack', 'route' => route('receivables.index')],
            'Supplier payables' => ['value' => $payable, 'money' => true, 'icon' => 'wallet2', 'route' => route('payables.index')],
            'Overdue receivables' => ['value' => (float) (clone $sales)->where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->sum('balance_amount'), 'money' => true, 'icon' => 'exclamation-circle', 'route' => route('receivables.aging')],
            'Overdue payables' => ['value' => (float) DB::table('supplier_invoices')->where('company_id', $companyId)->where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->sum('balance_amount'), 'money' => true, 'icon' => 'calendar-x', 'route' => route('payables.aging')],
            'Products' => ['value' => DB::table('products')->where('company_id', $companyId)->whereNull('deleted_at')->count(), 'icon' => 'box-seam'],
            'Customers' => ['value' => DB::table('customers')->where('company_id', $companyId)->where('is_walk_in', false)->where('code', '!=', 'WALK-IN')->whereNull('deleted_at')->count(), 'icon' => 'people'],
            'Suppliers' => ['value' => DB::table('suppliers')->where('company_id', $companyId)->whereNull('deleted_at')->count(), 'icon' => 'truck'],
            'Low stock items' => ['value' => DB::table('products')->where('company_id', $companyId)->where('is_active', true)->whereColumn('current_quantity', '<=', 'reorder_level')->count(), 'icon' => 'boxes', 'route' => route('stock.index')],
            'Open periods' => ['value' => DB::table('accounting_periods')->where('status', 'open')->count(), 'icon' => 'calendar-check'],
            'Accounts' => ['value' => DB::table('accounts')->where('company_id', $companyId)->whereNull('deleted_at')->count(), 'icon' => 'diagram-3', 'route' => route('accounting.accounts')],
        ];

        $days = collect(range(6, 0))->map(fn ($offset) => today()->subDays($offset));
        $trend = [
            'labels' => $days->map->format('d M')->all(),
            'sales' => $days->map(fn ($day) => (float) (clone $sales)->whereDate('sale_date', $day)->sum('grand_total'))->all(),
            'profit' => $days->map(fn ($day) => (float) (clone $sales)->whereDate('sale_date', $day)->sum('gross_profit'))->all(),
        ];
        $saleMix = [
            'cash' => (float) (clone $sales)->where('payment_type', 'cash')->sum('grand_total'),
            'credit' => (float) (clone $sales)->where('payment_type', 'credit')->sum('grand_total'),
        ];
        $development = ['percent' => 98, 'phase' => 'Phase 12 of 12', 'label' => 'Controls complete; production runtime upgrade and isolated restore drill remain'];

        return view('dashboard', compact('metrics', 'trend', 'saleMix', 'receivable', 'payable', 'development'));
    }
}
