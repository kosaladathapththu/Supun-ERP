<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManagementReportService
{
    public function dashboard(int $company, string $from, string $to): array
    {
        $sales = Sale::with(['items.product.category', 'items.product.brand'])->where('company_id', $company)->where('status', 'posted')->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->get();
        $returns = SaleReturn::with('items.product')->where('company_id', $company)->where('status', 'posted')->whereDate('return_date', '>=', $from)->whereDate('return_date', '<=', $to)->get();
        $grossSales = (float) $sales->sum('grand_total');
        $returnSales = (float) $returns->sum('total_amount');
        $grossProfit = (float) $sales->sum('gross_profit') - ($returnSales - (float) $returns->sum('cost_total'));
        $netSales = $grossSales - $returnSales;
        $orders = $sales->count();
        $units = (float) $sales->flatMap->items->sum('quantity') - (float) $returns->flatMap->items->sum('quantity');
        $trend = [];
        foreach ($sales as $sale) {
            $day = $sale->sale_date->format('Y-m-d');
            $trend[$day]['sales'] = ($trend[$day]['sales'] ?? 0) + (float) $sale->grand_total;
            $trend[$day]['profit'] = ($trend[$day]['profit'] ?? 0) + (float) $sale->gross_profit;
        }foreach ($returns as $return) {
            $day = $return->return_date->format('Y-m-d');
            $trend[$day]['sales'] = ($trend[$day]['sales'] ?? 0) - (float) $return->total_amount;
            $trend[$day]['profit'] = ($trend[$day]['profit'] ?? 0) - ((float) $return->total_amount - (float) $return->cost_total);
        }ksort($trend);
        $channels = $sales->groupBy('channel')->map(fn ($x) => (float) $x->sum('grand_total'))->all();
        $products = $this->productRows($sales, $returns);
        $inventory = Product::with(['category', 'brand'])->where('company_id', $company)->where('is_active', 1)->get();
        $stockValue = $inventory->sum(fn ($p) => (float) $p->current_quantity * (float) $p->average_cost);
        $payments = DB::table('sale_payments as p')->join('sales as s', 's.id', '=', 'p.sale_id')->where('s.company_id', $company)->where('p.status', 'posted')->whereDate('p.payment_date', '>=', $from)->whereDate('p.payment_date', '<=', $to)->selectRaw('p.payment_method, SUM(p.amount) total')->groupBy('p.payment_method')->pluck('total', 'payment_method')->map(fn ($x) => (float) $x)->all();

        return ['kpis' => ['net_sales' => $netSales, 'gross_profit' => $grossProfit, 'margin' => $netSales ? ($grossProfit / $netSales * 100) : 0, 'orders' => $orders, 'average_invoice' => $orders ? ($grossSales / $orders) : 0, 'units' => $units, 'returns' => $returnSales, 'stock_value' => $stockValue], 'trend' => $trend, 'channels' => $channels, 'payments' => $payments, 'top_products' => $products->sortByDesc('sales')->take(10)->values(), 'low_stock' => $inventory->filter(fn ($p) => (float) $p->current_quantity <= (float) $p->reorder_level)->sortBy('current_quantity')->take(10)->values()];
    }

    public function profitability(int $company, string $from, string $to): Collection
    {
        $sales = Sale::with(['items.product.category', 'items.product.brand'])->where('company_id', $company)->where('status', 'posted')->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->get();
        $returns = SaleReturn::with('items.product')->where('company_id', $company)->where('status', 'posted')->whereDate('return_date', '>=', $from)->whereDate('return_date', '<=', $to)->get();

        return $this->productRows($sales, $returns)->sortByDesc('profit')->values();
    }

    public function inventory(int $company): Collection
    {
        return Product::with(['category', 'brand'])->where('company_id', $company)->where('is_active', 1)->get()->map(fn ($p) => (object) ['product' => $p, 'quantity' => (float) $p->current_quantity, 'average_cost' => (float) $p->average_cost, 'value' => (float) $p->current_quantity * (float) $p->average_cost, 'status' => (float) $p->current_quantity <= 0 ? 'out_of_stock' : ((float) $p->current_quantity <= (float) $p->reorder_level ? 'low_stock' : 'healthy')])->sortByDesc('value')->values();
    }

    private function productRows(Collection $sales, Collection $returns): Collection
    {
        $rows = [];
        foreach ($sales->flatMap->items as $i) {
            $id = $i->product_id;
            $rows[$id] ??= (object) ['product' => $i->product, 'quantity' => 0.0, 'sales' => 0.0, 'cost' => 0.0, 'profit' => 0.0, 'margin' => 0.0];
            $rows[$id]->quantity += (float) $i->quantity;
            $rows[$id]->sales += (float) $i->line_total;
            $rows[$id]->cost += (float) $i->cost_total;
        }foreach ($returns->flatMap->items as $i) {
            $id = $i->product_id;
            if (! isset($rows[$id])) {
                continue;
            }$rows[$id]->quantity -= (float) $i->quantity;
            $rows[$id]->sales -= (float) $i->line_total;
            $rows[$id]->cost -= (float) $i->cost_total;
        }foreach ($rows as $row) {
            $row->profit = $row->sales - $row->cost;
            $row->margin = $row->sales ? ($row->profit / $row->sales * 100) : 0;
        }

return collect($rows);
    }
}
