<?php

namespace App\Services;

use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPaymentService
{
    public function post(array $data, $user): SupplierPayment
    {
        return DB::transaction(function () use ($data, $user) {
        $allocated = '0';
        $lines = [];
        foreach (($data['allocations'] ?? []) as $id => $value) {
        if (! $value || bccomp((string) $value, '0', 2) <= 0) {
        continue;
        }$invoice = SupplierInvoice::where('company_id', $user->company_id)->where('supplier_id', $data['supplier_id'])->where('status', 'posted')->lockForUpdate()->findOrFail($id);
        if (bccomp((string) $value, (string) $invoice->balance_amount, 2) > 0) {
        throw ValidationException::withMessages(['allocations' => "Allocation exceeds {$invoice->document_number} balance."]);
        }$allocated = bcadd($allocated, (string) $value, 2);
        $lines[] = [$invoice, (string) $value];
        }$amount = (string) $data['amount'];
        if (bccomp($allocated, $amount, 2) > 0) {
        throw ValidationException::withMessages(['allocations' => 'Allocations cannot exceed payment.']);
        }$payment = SupplierPayment::create(['company_id' => $user->company_id, 'supplier_id' => $data['supplier_id'], 'paid_by' => $user->id, 'payment_number' => app(DocumentNumberService::class)->next($user->company_id, 'supplier_payment', 'SP'), 'payment_date' => $data['payment_date'], 'payment_method' => $data['payment_method'], 'amount' => $amount, 'allocated_amount' => $allocated, 'unapplied_amount' => bcsub($amount, $allocated, 2), 'reference' => $data['reference'] ?? null]);
        foreach ($lines as [$invoice,$value]) {
        $payment->allocations()->create(['supplier_invoice_id' => $invoice->id, 'amount' => $value]);
        $paid = bcadd((string) $invoice->paid_amount, $value, 2);
        $balance = bcsub((string) $invoice->balance_amount, $value, 2);
        $invoice->update(['paid_amount' => $paid, 'balance_amount' => $balance, 'payment_status' => bccomp($balance, '0', 2) <= 0 ? 'paid' : 'partially_paid']);
        }$journal = [];
        if (bccomp($allocated, '0', 2) > 0) {
        $journal[] = ['account_code' => '2100', 'debit' => $allocated, 'supplier_id' => $data['supplier_id']];
        }$unapplied = bcsub($amount, $allocated, 2);
        if (bccomp($unapplied, '0', 2) > 0) {
        $journal[] = ['account_code' => '1150', 'debit' => $unapplied, 'supplier_id' => $data['supplier_id']];
        }$journal[] = ['account_code' => $data['payment_method'] === 'cash' ? '1110' : '1120', 'credit' => $amount];
        app(JournalPostingService::class)->post($user->company_id, $data['payment_date'], SupplierPayment::class, $payment->id, $payment->payment_number, "Supplier payment {$payment->payment_number}", $journal, $user->id);

        return $payment;
        });
    }
}
