<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Services\DocumentNumberService;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('account')->where('company_id', $request->user()->company_id);
        $totals = (clone $query)->selectRaw('COALESCE(SUM(amount),0) billed, COALESCE(SUM(paid_amount),0) paid, COALESCE(SUM(balance_amount),0) payable')->first();

        return view('expenses.index', ['expenses' => $query->latest('expense_date')->paginate(20), 'totals' => $totals]);
    }

    public function create(Request $request)
    {
        return view('expenses.create', ['accounts' => $this->accounts($request->user()->company_id)]);
    }

    public function store(Request $request)
    {
        $company = $request->user()->company_id;
        $data = $request->validate([
            'expense_date' => ['required', 'date'], 'due_date' => ['nullable', 'date', 'after_or_equal:expense_date'],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('company_id', $company)->where('is_active', 1))],
            'payee' => ['required', 'string', 'max:150'], 'amount' => ['required', 'numeric', 'gt:0'],
            'paid_amount' => ['required', 'numeric', 'min:0', 'lte:amount'],
            'payment_method' => [Rule::requiredIf(fn () => (float) $request->input('paid_amount') > 0), 'nullable', Rule::in(['cash', 'cheque', 'bank_transfer', 'online_payment'])],
            'reference' => ['nullable', 'string', 'max:150'], 'description' => ['required', 'string', 'max:1000'],
        ]);
        $expense = DB::transaction(function () use ($data, $request, $company) {
            $paid = (string) $data['paid_amount'];
            $balance = bcsub((string) $data['amount'], $paid, 2);
            $status = bccomp($balance, '0', 2) === 0 ? 'paid' : (bccomp($paid, '0', 2) > 0 ? 'partially_paid' : 'unpaid');
            $expense = Expense::create(array_merge($data, ['company_id' => $company, 'created_by' => $request->user()->id, 'document_number' => app(DocumentNumberService::class)->next($company, 'expense', 'EXP'), 'paid_amount' => $paid, 'balance_amount' => $balance, 'payment_status' => $status, 'payment_method' => $data['payment_method'] ?? 'payable']));
            $account = Account::findOrFail($data['account_id']);
            $lines = [['account_code' => $account->code, 'debit' => $data['amount']]];
            if (bccomp($paid, '0', 2) > 0) {
                $lines[] = ['account_code' => $this->cashAccount($data['payment_method']), 'credit' => $paid];
                ExpensePayment::create(['company_id' => $company, 'expense_id' => $expense->id, 'paid_by' => $request->user()->id, 'payment_number' => app(DocumentNumberService::class)->next($company, 'expense_payment', 'EP'), 'payment_date' => $data['expense_date'], 'payment_method' => $data['payment_method'], 'amount' => $paid, 'reference' => $data['reference'] ?? null]);
            }
            if (bccomp($balance, '0', 2) > 0) {
                $lines[] = ['account_code' => '2200', 'credit' => $balance];
            }
            app(JournalPostingService::class)->post($company, $data['expense_date'], Expense::class, $expense->id, $expense->document_number, "Expense bill {$expense->document_number}", $lines, $request->user()->id);

            return $expense;
        });

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense bill posted successfully.');
    }

    public function show(Request $request, $expense)
    {
        $expense = Expense::with(['account', 'payments'])->where('company_id', $request->user()->company_id)->findOrFail($expense);

        return view('expenses.show', compact('expense'));
    }

    public function payment(Request $request, $expense)
    {
        $expense = Expense::where('company_id', $request->user()->company_id)->where('balance_amount', '>', 0)->findOrFail($expense);

        return view('expenses.payment', compact('expense'));
    }

    public function storePayment(Request $request, $expense)
    {
        $company = $request->user()->company_id;
        $expense = Expense::where('company_id', $company)->findOrFail($expense);
        $data = $request->validate(['payment_date' => ['required', 'date'], 'payment_method' => ['required', Rule::in(['cash', 'cheque', 'bank_transfer', 'online_payment'])], 'amount' => ['required', 'numeric', 'gt:0'], 'reference' => ['nullable', 'string', 'max:150']]);
        DB::transaction(function () use ($request, $expense, $data, $company) {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            if (bccomp((string) $data['amount'], (string) $expense->balance_amount, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the outstanding balance.']);
            }
            $payment = ExpensePayment::create(['company_id' => $company, 'expense_id' => $expense->id, 'paid_by' => $request->user()->id, 'payment_number' => app(DocumentNumberService::class)->next($company, 'expense_payment', 'EP'), 'payment_date' => $data['payment_date'], 'payment_method' => $data['payment_method'], 'amount' => $data['amount'], 'reference' => $data['reference'] ?? null]);
            $paid = bcadd((string) $expense->paid_amount, (string) $data['amount'], 2);
            $balance = bcsub((string) $expense->amount, $paid, 2);
            $expense->update(['paid_amount' => $paid, 'balance_amount' => $balance, 'payment_status' => bccomp($balance, '0', 2) === 0 ? 'paid' : 'partially_paid']);
            app(JournalPostingService::class)->post($company, $data['payment_date'], ExpensePayment::class, $payment->id, $payment->payment_number, "Payment for {$expense->document_number}", [['account_code' => '2200', 'debit' => $data['amount']], ['account_code' => $this->cashAccount($data['payment_method']), 'credit' => $data['amount']]], $request->user()->id);
        });

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense payment posted successfully.');
    }

    private function accounts(int $company)
    {
        return Account::where('company_id', $company)->whereBetween('code', ['6000', '6999'])->where('allow_manual_posting', 1)->where('is_active', 1)->orderBy('code')->get();
    }

    private function cashAccount(string $method): string
    {
        return $method === 'cash' ? '1110' : '1120';
    }
}
