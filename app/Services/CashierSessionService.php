<?php

namespace App\Services;

use App\Models\CashierSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierSessionService
{
    public function open(User $user, array $data): CashierSession
    {
        return DB::transaction(function () use ($user, $data) {
            $exists = CashierSession::where('company_id', $user->company_id)
                ->where('user_id', $user->id)->where('status', 'open')->lockForUpdate()->exists();
            if ($exists) {
                throw ValidationException::withMessages(['opening_cash' => 'This cashier already has an open session.']);
            }

            return CashierSession::create([
                'company_id' => $user->company_id, 'user_id' => $user->id,
                'opened_by' => $user->id, 'business_date' => now()->toDateString(),
                'opened_at' => now(), 'opening_cash' => $data['opening_cash'],
                'expected_cash' => $data['opening_cash'], 'opening_notes' => $data['opening_notes'] ?? null,
            ]);
        });
    }

    public function totals(CashierSession $session): array
    {
        $from = $session->opened_at;
        $to = $session->closed_at ?: now();
        $company = $session->company_id;
        $user = $session->user_id;

        $cashSales = DB::table('sale_payments as p')->join('sales as s', 's.id', '=', 'p.sale_id')
            ->where('s.company_id', $company)->where('p.received_by', $user)->where('p.status', 'posted')
            ->where('p.payment_method', 'cash')->whereBetween('p.payment_date', [$from, $to])->sum('p.amount');
        $receipts = DB::table('customer_receipts')->where('company_id', $company)->where('received_by', $user)
            ->where('status', 'posted')->where('payment_method', 'cash')->whereBetween('created_at', [$from, $to])->sum('amount');
        $expenses = DB::table('expenses')->where('company_id', $company)->where('created_by', $user)
            ->where('status', 'posted')->where('payment_method', 'cash')->whereBetween('created_at', [$from, $to])->sum('amount');
        $supplierPayments = DB::table('supplier_payments')->where('company_id', $company)->where('paid_by', $user)
            ->where('status', 'posted')->where('payment_method', 'cash')->whereBetween('created_at', [$from, $to])->sum('amount');
        $refunds = DB::table('sale_returns')->where('company_id', $company)->where('created_by', $user)
            ->where('status', 'posted')->where('settlement_type', 'cash_refund')->whereBetween('return_date', [$from, $to])->sum('total_amount');
        $expected = (float) $session->opening_cash + (float) $cashSales + (float) $receipts - (float) $expenses - (float) $supplierPayments - (float) $refunds;

        return ['cash_sales' => $cashSales, 'customer_receipts' => $receipts, 'cash_expenses' => $expenses,
            'supplier_payments' => $supplierPayments, 'cash_refunds' => $refunds, 'expected_cash' => $expected];
    }

    public function close(CashierSession $session, User $user, array $data): CashierSession
    {
        return DB::transaction(function () use ($session, $user, $data) {
            $session = CashierSession::where('company_id', $user->company_id)->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($session->status !== 'open') {
                throw ValidationException::withMessages(['actual_cash' => 'This cashier session is already closed.']);
            }
            $totals = $this->totals($session);
            $actual = (float) $data['actual_cash'];
            $session->update($totals + ['actual_cash' => $actual, 'variance' => $actual - (float) $totals['expected_cash'],
                'closed_by' => $user->id, 'closed_at' => now(), 'status' => 'closed', 'closing_notes' => $data['closing_notes'] ?? null]);

            return $session->refresh();
        });
    }
}
