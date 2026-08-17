<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialStatementService
{
    public function balances(int $company, string $to, ?string $from = null): Collection
    {
        $accounts = Account::with('type')->where('company_id', $company)->where('is_active', 1)->orderBy('code')->get();
        $q = DB::table('journal_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')->where('e.company_id', $company)->where('e.status', 'posted')->whereDate('e.entry_date', '<=', $to);
        if ($from) {
            $q->whereDate('e.entry_date', '>=', $from);
        }$totals = $q->select('l.account_id', DB::raw('SUM(l.debit) debit'), DB::raw('SUM(l.credit) credit'))->groupBy('l.account_id')->get()->keyBy('account_id');

        return $accounts->map(function ($a) use ($totals) {
        $x = $totals->get($a->id);
        $debit = (float) ($x->debit ?? 0);
        $credit = (float) ($x->credit ?? 0);
        $a->statement_balance = $a->type->normal_balance === 'debit' ? $debit - $credit : $credit - $debit;
        $a->raw_debit = $debit;
        $a->raw_credit = $credit;

        return $a;
        });
    }

    public function profitLoss(int $company, string $from, string $to): array
    {
        $rows = $this->balances($company, $to, $from)->whereIn('type.code', ['REVENUE', 'COGS', 'EXPENSE'])->filter(fn ($a) => abs($a->statement_balance) > .004);
        $revenue = $rows->where('type.code', 'REVENUE')->sum('statement_balance');
        $cogs = $rows->where('type.code', 'COGS')->sum('statement_balance');
        $expenses = $rows->where('type.code', 'EXPENSE')->sum('statement_balance');

        return compact('rows', 'revenue', 'cogs', 'expenses') + ['gross_profit' => $revenue - $cogs, 'net_profit' => $revenue - $cogs - $expenses];
    }

    public function balanceSheet(int $company, string $to): array
    {
        $all = $this->balances($company, $to);
        $rows = $all->whereIn('type.code', ['ASSET', 'LIABILITY', 'EQUITY'])->filter(fn ($a) => abs($a->statement_balance) > .004);
        $assets = $rows->where('type.code', 'ASSET')->sum('statement_balance');
        $liabilities = $rows->where('type.code', 'LIABILITY')->sum('statement_balance');
        $equity = $rows->where('type.code', 'EQUITY')->sum('statement_balance');
        $pl = $all->where('type.code', 'REVENUE')->sum('statement_balance') - $all->where('type.code', 'COGS')->sum('statement_balance') - $all->where('type.code', 'EXPENSE')->sum('statement_balance');

        return compact('rows', 'assets', 'liabilities', 'equity') + ['current_earnings' => $pl, 'liabilities_equity' => $liabilities + $equity + $pl, 'difference' => $assets - ($liabilities + $equity + $pl)];
    }

    public function cashFlow(int $company, string $from, string $to): array
    {
        $before = date('Y-m-d', strtotime($from.' -1 day'));
        $opening = $this->balances($company, $before)->keyBy('code');
        $closing = $this->balances($company, $to)->keyBy('code');
        $change = fn (string $code) => (float) ($closing->get($code)?->statement_balance ?? 0) - (float) ($opening->get($code)?->statement_balance ?? 0);
        $pl = $this->profitLoss($company, $from, $to);
        $adjustments = ['Increase in accounts receivable' => -$change('1130'), 'Increase in inventory' => -$change('1140'), 'Increase in supplier advances' => -$change('1150'), 'Increase in accounts payable' => $change('2100'), 'Increase in customer advances' => $change('2150'), 'Increase in accrued expenses' => $change('2200'), 'Increase in taxes payable' => $change('2300')];
        $operating = $pl['net_profit'] + array_sum($adjustments);
        $investing = 0;
        foreach (['1210', '1220', '1230'] as $code) {
            $investing -= $change($code);
        }$financing = $change('3100') + $change('3200');
        $openingCash = (float) ($opening->get('1110')?->statement_balance ?? 0) + (float) ($opening->get('1120')?->statement_balance ?? 0);
        $closingCash = (float) ($closing->get('1110')?->statement_balance ?? 0) + (float) ($closing->get('1120')?->statement_balance ?? 0);
        $net = $operating + $investing + $financing;

        return compact('adjustments', 'operating', 'investing', 'financing', 'openingCash', 'closingCash', 'net') + ['net_profit' => $pl['net_profit'], 'actual_change' => $closingCash - $openingCash, 'difference' => ($closingCash - $openingCash) - $net];
    }
}
