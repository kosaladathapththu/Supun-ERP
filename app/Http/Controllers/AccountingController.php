<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\ReportXlsxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountingController extends Controller
{
    public function accounts(Request $r)
    {
        $company = $r->user()->company_id;
        $q = Account::with(['type', 'parent'])->where('company_id', $company);
        if ($r->filled('search')) {
            $search = $r->search;
            $q->where(fn ($x) => $x->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }if ($r->filled('type')) {
            $q->where('account_type_id', $r->type);
        }$balances = JournalLine::select('account_id', DB::raw('SUM(debit) debit_total'), DB::raw('SUM(credit) credit_total'))->whereHas('entry', fn ($x) => $x->where('company_id', $company)->where('status', 'posted'))->groupBy('account_id')->get()->keyBy('account_id');
        $accounts = $q->orderBy('code')->paginate(50)->withQueryString();
        $types = \App\Models\AccountType::orderBy('display_order')->get();

        return view('accounting.accounts', compact('accounts', 'balances', 'types'));
    }

    public function createAccount(Request $r)
    {
        return view('accounting.account-form', $this->accountFormData($r));
    }

    public function storeAccount(Request $r)
    {
        $company = $r->user()->company_id;
        $data = $this->validateAccount($r, $company);
        $data['company_id'] = $company;
        $data['opening_balance'] = 0;
        $data['opening_balance_date'] = null;
        Account::create($data);

        return redirect()->route('accounting.accounts')->with('success', 'Account created successfully.');
    }

    public function editAccount(Request $r, Account $account)
    {
        $this->ensureCompanyAccount($r, $account);

        return view('accounting.account-form', $this->accountFormData($r, $account));
    }

    public function updateAccount(Request $r, Account $account)
    {
        $this->ensureCompanyAccount($r, $account);
        $data = $this->validateAccount($r, $r->user()->company_id, $account);
        abort_if($data['parent_id'] && $this->wouldCreateCycle($account, (int) $data['parent_id']), 422, 'An account cannot be placed below itself or one of its child accounts.');
        $account->update($data);

        return redirect()->route('accounting.accounts')->with('success', 'Account updated successfully.');
    }

    private function accountFormData(Request $r, ?Account $account = null): array
    {
        $company = $r->user()->company_id;

        return ['account' => $account, 'types' => \App\Models\AccountType::orderBy('display_order')->get(), 'parentAccounts' => Account::with('type')->where('company_id', $company)->when($account, fn ($q) => $q->where('id', '!=', $account->id))->orderBy('code')->get()];
    }

    private function validateAccount(Request $r, int $company, ?Account $account = null): array
    {
        $data = $r->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('accounts')->where(fn ($q) => $q->where('company_id', $company))->ignore($account?->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'account_type_id' => ['required', 'integer', Rule::exists('account_types', 'id')],
            'parent_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('company_id', $company)->whereNull('deleted_at'))],
            'is_control_account' => ['required', 'boolean'],
            'allow_manual_posting' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);
        if (! empty($data['parent_id'])) {
            $parent = Account::where('company_id', $company)->findOrFail($data['parent_id']);
            abort_if((int) $parent->account_type_id !== (int) $data['account_type_id'], 422, 'The parent account must have the same account type.');
        }
        if ($data['is_control_account']) {
            $data['allow_manual_posting'] = false;
        }

        return $data;
    }

    private function ensureCompanyAccount(Request $r, Account $account): void
    {
        abort_unless($account->company_id === $r->user()->company_id, 404);
    }

    private function wouldCreateCycle(Account $account, int $parentId): bool
    {
        for ($parent = Account::find($parentId); $parent; $parent = $parent->parent) {
            if ($parent->id === $account->id) return true;
        }

        return false;
    }

    public function journals(Request $r)
    {
        $q = JournalEntry::where('company_id', $r->user()->company_id);
        if ($r->filled('from')) {
            $q->whereDate('entry_date', '>=', $r->from);
        }if ($r->filled('to')) {
            $q->whereDate('entry_date', '<=', $r->to);
        }

return view('accounting.journals', ['entries' => $q->latest('entry_date')->latest('id')->paginate(25)->withQueryString()]);
    }

    public function show(Request $r, JournalEntry $journal)
    {
        abort_unless($journal->company_id === $r->user()->company_id, 404);
        $journal->load('lines.account');

        return view('accounting.show', compact('journal'));
    }

    public function trialBalance(Request $r)
    {
        $company = $r->user()->company_id;
        $to = $r->input('to', now()->toDateString());
        $accounts = Account::with('type')->where('company_id', $company)->where('is_active', 1)->orderBy('code')->get();
        $totals = JournalLine::select('account_id', DB::raw('SUM(debit) debit_total'), DB::raw('SUM(credit) credit_total'))->whereHas('entry', fn ($q) => $q->where('company_id', $company)->where('status', 'posted')->whereDate('entry_date', '<=', $to))->groupBy('account_id')->get()->keyBy('account_id');

        return view('accounting.trial-balance', compact('accounts', 'totals', 'to'));
    }

    public function exportTrialBalance(Request $r, ReportXlsxService $excel)
    {
        $company = $r->user()->company_id;
        $to = $r->input('to', now()->toDateString());
        $accounts = Account::with('type')->where('company_id', $company)->where('is_active', 1)->orderBy('code')->get();
        $totals = JournalLine::select('account_id', DB::raw('SUM(debit) debit_total'), DB::raw('SUM(credit) credit_total'))->whereHas('entry', fn ($q) => $q->where('company_id', $company)->where('status', 'posted')->whereDate('entry_date', '<=', $to))->groupBy('account_id')->get()->keyBy('account_id');
        $rows = $accounts->map(function ($account) use ($totals) { $total = $totals->get($account->id); return [$account->code, $account->name, $account->type->name, (float) ($total?->debit_total ?? 0), (float) ($total?->credit_total ?? 0)]; });
        $path = $excel->create('Trial Balance', "As at {$to}", ['Code', 'Account', 'Type', 'Debit', 'Credit'], $rows, ['D', 'E'], ['Total debit' => (float) $rows->sum(3), 'Total credit' => (float) $rows->sum(4)]);
        return response()->download($path, "trial-balance-{$to}.xlsx")->deleteFileAfterSend(true);
    }

    public function ledger(Request $r, Account $account)
    {
        abort_unless($account->company_id === $r->user()->company_id, 404);
        $lines = JournalLine::with('entry')->where('account_id', $account->id)->whereHas('entry', fn ($q) => $q->where('status', 'posted'))->get()->sortBy(fn ($x) => $x->entry->entry_date->format('Y-m-d').str_pad($x->id, 10, '0', STR_PAD_LEFT));

        return view('accounting.ledger', compact('account', 'lines'));
    }
}
