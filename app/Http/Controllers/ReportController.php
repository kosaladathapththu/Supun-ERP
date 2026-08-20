<?php

namespace App\Http\Controllers;

use App\Services\ManagementReportService;
use App\Services\ReportXlsxService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function dates(Request $r): array
    {
        $to = $r->input('to', now()->toDateString());
        $from = $r->input('from', date('Y-m-01', strtotime($to)));
        abort_unless(strtotime($from) !== false && strtotime($to) !== false && $from <= $to, 422, 'Invalid report date range.');

        return [$from, $to];
    }

    public function index(Request $r, ManagementReportService $s)
    {
        [$from,$to] = $this->dates($r);
        $data = $s->dashboard($r->user()->company_id, $from, $to);

        return view('reports.index', compact('from', 'to', 'data'));
    }

    public function profitability(Request $r, ManagementReportService $s)
    {
        [$from,$to] = $this->dates($r);
        $rows = $s->profitability($r->user()->company_id, $from, $to);

        return view('reports.profitability', compact('from', 'to', 'rows'));
    }

    public function inventory(Request $r, ManagementReportService $s)
    {
        $rows = $s->inventory($r->user()->company_id);

        return view('reports.inventory', compact('rows'));
    }

    public function exportDashboard(Request $r, ManagementReportService $s, ReportXlsxService $excel)
    {
        [$from,$to] = $this->dates($r);
        $data = $s->dashboard($r->user()->company_id, $from, $to);
        $labels = ['net_sales' => 'Net sales', 'gross_profit' => 'Gross profit', 'margin' => 'Gross margin %', 'orders' => 'Invoices', 'average_invoice' => 'Average invoice', 'units' => 'Units sold', 'returns' => 'Returns', 'stock_value' => 'Stock value'];
        $rows = collect($labels)->map(fn ($label, $key) => [$label, (float) $data['kpis'][$key]])->values();
        $path = $excel->create('Management Intelligence Summary', "Period {$from} to {$to}", ['Metric', 'Value'], $rows, ['B']);
        return response()->download($path, "management-report-{$from}-to-{$to}.xlsx")->deleteFileAfterSend(true);
    }

    public function exportProfitability(Request $r, ManagementReportService $s, ReportXlsxService $excel)
    {
        [$from,$to] = $this->dates($r);
        $rows = $s->profitability($r->user()->company_id, $from, $to);

        $data = $rows->map(fn ($x) => [$x->product->item_code, $x->product->name, $x->product->category?->name, $x->product->brand?->name, (float) $x->quantity, (float) $x->sales, (float) $x->cost, (float) $x->profit, (float) $x->margin]);
        $path = $excel->create('Product Profitability', "Period {$from} to {$to}", ['Item Code', 'Product', 'Category', 'Brand', 'Net Quantity', 'Net Sales', 'Cost', 'Gross Profit', 'Margin %'], $data, ['F', 'G', 'H']);
        return response()->download($path, "product-profitability-{$from}-to-{$to}.xlsx")->deleteFileAfterSend(true);
    }

    public function exportInventory(Request $r, ManagementReportService $s, ReportXlsxService $excel)
    {
        $rows = $s->inventory($r->user()->company_id);

        $data = $rows->map(fn ($x) => [$x->product->item_code, $x->product->name, $x->product->category?->name, $x->product->brand?->name, (float) $x->quantity, (float) $x->average_cost, (float) $x->value, str($x->status)->headline()->toString()]);
        $path = $excel->create('Inventory Valuation', 'As at '.now()->toDateString(), ['Item Code', 'Product', 'Category', 'Brand', 'Quantity', 'Average Cost', 'Stock Value', 'Status'], $data, ['F', 'G'], ['Total stock value' => (float) $rows->sum('value')]);
        return response()->download($path, 'inventory-valuation-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend(true);
    }
}
