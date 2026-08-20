<?php

namespace App\Http\Controllers;

use App\Services\FinancialStatementService;
use App\Services\ReportXlsxService;
use Illuminate\Http\Request;

class StatementController extends Controller
{
    private function dates(Request $r): array
    {
        $to = $r->input('to', now()->toDateString());
        $from = $r->input('from', date('Y-01-01', strtotime($to)));
        abort_unless(strtotime($from) !== false && strtotime($to) !== false && $from <= $to, 422, 'Invalid statement date range.');

        return [$from, $to];
    }

    public function index(Request $r)
    {
        [$from,$to] = $this->dates($r);

        return view('statements.index', compact('from', 'to'));
    }

    public function profitLoss(Request $r, FinancialStatementService $s)
    {
        [$from,$to] = $this->dates($r);
        $statement = $s->profitLoss($r->user()->company_id, $from, $to);

        return view('statements.profit-loss', compact('from', 'to', 'statement'));
    }

    public function balanceSheet(Request $r, FinancialStatementService $s)
    {
        [, $to] = $this->dates($r);
        $statement = $s->balanceSheet($r->user()->company_id, $to);

        return view('statements.balance-sheet', compact('to', 'statement'));
    }

    public function cashFlow(Request $r, FinancialStatementService $s)
    {
        [$from,$to] = $this->dates($r);
        $statement = $s->cashFlow($r->user()->company_id, $from, $to);

        return view('statements.cash-flow', compact('from', 'to', 'statement'));
    }

    public function reconciliation(Request $r, FinancialStatementService $s)
    {
        [, $to] = $this->dates($r);
        $company = $r->user()->company_id;
        $bs = $s->balanceSheet($company, $to);
        $balances = $s->balances($company, $to)->keyBy('code');
        $checks = [['name' => 'Journal debits equal credits', 'book' => (float) $balances->sum('raw_debit'), 'subledger' => (float) $balances->sum('raw_credit')], ['name' => 'Accounts receivable control vs customer balances', 'book' => (float) ($balances->get('1130')?->statement_balance ?? 0), 'subledger' => (float) \DB::table('sales')->where('company_id', $company)->where('status', 'posted')->whereDate('sale_date', '<=', $to)->sum('balance_amount')], ['name' => 'Accounts payable control vs supplier balances', 'book' => (float) ($balances->get('2100')?->statement_balance ?? 0), 'subledger' => (float) \DB::table('supplier_invoices')->where('company_id', $company)->where('status', 'posted')->whereDate('invoice_date', '<=', $to)->sum('balance_amount')], ['name' => 'Accrued expenses control vs unpaid expense bills', 'book' => (float) ($balances->get('2200')?->statement_balance ?? 0), 'subledger' => (float) \DB::table('expenses')->where('company_id', $company)->where('status', 'posted')->whereDate('expense_date', '<=', $to)->sum('balance_amount')], ['name' => 'Inventory control vs current stock valuation', 'book' => (float) ($balances->get('1140')?->statement_balance ?? 0), 'subledger' => (float) \DB::table('products')->where('company_id', $company)->whereNull('deleted_at')->selectRaw('SUM(current_quantity * average_cost) total')->value('total')], ['name' => 'Balance-sheet equation', 'book' => $bs['assets'], 'subledger' => $bs['liabilities_equity']]];

        return view('statements.reconciliation', compact('to', 'checks'));
    }

    public function exportProfitLoss(Request $r, FinancialStatementService $service, ReportXlsxService $excel)
    {
        [$from,$to] = $this->dates($r); $statement = $service->profitLoss($r->user()->company_id, $from, $to);
        $rows = $statement['rows']->filter(fn ($x) => in_array($x->type->code, ['REVENUE', 'COGS', 'EXPENSE']))->map(fn ($x) => [$x->type->name, $x->code, $x->name, (float) $x->statement_balance])->values();
        $path = $excel->create('Profit & Loss Statement', "Period {$from} to {$to}", ['Section', 'Code', 'Account', 'Amount'], $rows, ['D'], ['Total revenue' => $statement['revenue'], 'Gross profit' => $statement['gross_profit'], 'Net profit / (loss)' => $statement['net_profit']]);
        return response()->download($path, "profit-and-loss-{$from}-to-{$to}.xlsx")->deleteFileAfterSend(true);
    }

    public function exportBalanceSheet(Request $r, FinancialStatementService $service, ReportXlsxService $excel)
    {
        [, $to] = $this->dates($r); $statement = $service->balanceSheet($r->user()->company_id, $to);
        $rows = $statement['rows']->filter(fn ($x) => in_array($x->type->code, ['ASSET', 'LIABILITY', 'EQUITY']))->map(fn ($x) => [$x->type->name, $x->code, $x->name, (float) $x->statement_balance])->values();
        $path = $excel->create('Statement of Financial Position', "As at {$to}", ['Section', 'Code', 'Account', 'Amount'], $rows, ['D'], ['Current earnings' => $statement['current_earnings'], 'Total assets' => $statement['assets'], 'Total liabilities and equity' => $statement['liabilities_equity'], 'Reconciliation difference' => $statement['difference']]);
        return response()->download($path, "balance-sheet-{$to}.xlsx")->deleteFileAfterSend(true);
    }

    public function exportCashFlow(Request $r, FinancialStatementService $service, ReportXlsxService $excel)
    {
        [$from,$to] = $this->dates($r); $statement = $service->cashFlow($r->user()->company_id, $from, $to);
        $rows = collect([['Net profit / (loss)', $statement['net_profit']]])->concat(collect($statement['adjustments'])->map(fn ($amount, $label) => [$label, (float) $amount]))->concat([['Net cash from operating activities', $statement['operating']], ['Investing activities', $statement['investing']], ['Financing activities', $statement['financing']], ['Calculated net cash movement', $statement['net']], ['Opening cash and bank', $statement['openingCash']], ['Closing cash and bank', $statement['closingCash']]]);
        $path = $excel->create('Statement of Cash Flows', "Period {$from} to {$to}", ['Cash-flow Activity', 'Amount'], $rows, ['B'], ['Reconciliation difference' => $statement['difference']]);
        return response()->download($path, "cash-flow-{$from}-to-{$to}.xlsx")->deleteFileAfterSend(true);
    }

    public function exportReconciliation(Request $r, FinancialStatementService $service, ReportXlsxService $excel)
    {
        [, $to] = $this->dates($r); $company = $r->user()->company_id; $balances = $service->balances($company, $to)->keyBy('code'); $bs = $service->balanceSheet($company, $to);
        $checks = $this->checks($company, $to, $balances, $bs);
        $rows = collect($checks)->map(fn ($x) => [$x['name'], (float) $x['book'], (float) $x['subledger'], (float) $x['book'] - (float) $x['subledger'], abs((float) $x['book'] - (float) $x['subledger']) < .01 ? 'PASSED' : 'REVIEW']);
        $path = $excel->create('Financial Control Reconciliation', "As at {$to}", ['Control Check', 'Book / Control', 'Compared Amount', 'Difference', 'Result'], $rows, ['B', 'C', 'D']);
        return response()->download($path, "financial-reconciliation-{$to}.xlsx")->deleteFileAfterSend(true);
    }

    private function checks(int $company, string $to, $balances, array $bs): array
    {
        return [['name' => 'Journal debits equal credits', 'book' => (float) $balances->sum('raw_debit'), 'subledger' => (float) $balances->sum('raw_credit')], ['name' => 'Accounts receivable control vs customer balances', 'book' => (float) ($balances->get('1130')?->statement_balance ?? 0), 'subledger' => (float) \DB::table('sales')->where('company_id', $company)->where('status', 'posted')->whereDate('sale_date', '<=', $to)->sum('balance_amount')], ['name' => 'Accounts payable control vs supplier balances', 'book' => (float) ($balances->get('2100')?->statement_balance ?? 0), 'subledger' => (float) \DB::table('supplier_invoices')->where('company_id', $company)->where('status', 'posted')->whereDate('invoice_date', '<=', $to)->sum('balance_amount')], ['name' => 'Accrued expenses control vs unpaid expense bills', 'book' => (float) ($balances->get('2200')?->statement_balance ?? 0), 'subledger' => (float) \DB::table('expenses')->where('company_id', $company)->where('status', 'posted')->whereDate('expense_date', '<=', $to)->sum('balance_amount')], ['name' => 'Inventory control vs current stock valuation', 'book' => (float) ($balances->get('1140')?->statement_balance ?? 0), 'subledger' => (float) \DB::table('products')->where('company_id', $company)->whereNull('deleted_at')->selectRaw('SUM(current_quantity * average_cost) total')->value('total')], ['name' => 'Balance-sheet equation', 'book' => $bs['assets'], 'subledger' => $bs['liabilities_equity']]];
    }
}
