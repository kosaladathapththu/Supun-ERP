<?php

namespace App\Services;

use App\Models\DebitNote;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPaymentService
{
    /**
     * Return one consistent supplier payable position for screens and posting.
     * Opening payable is reduced only after invoice balances have been settled.
     */
    public function position(int $companyId, int $supplierId): array
    {
        $supplier = Supplier::where('company_id', $companyId)->findOrFail($supplierId);

        $invoiceTotal = (string) SupplierInvoice::where('company_id', $companyId)
            ->where('supplier_id', $supplierId)->where('status', 'posted')->sum('total_amount');
        $invoiceOutstanding = (string) SupplierInvoice::where('company_id', $companyId)
            ->where('supplier_id', $supplierId)->where('status', 'posted')->sum('balance_amount');
        $payments = (string) SupplierPayment::where('company_id', $companyId)
            ->where('supplier_id', $supplierId)->where('status', 'posted')->sum('amount');
        $credits = (string) DebitNote::where('company_id', $companyId)
            ->where('supplier_id', $supplierId)->where('status', '!=', 'voided')->sum('amount');

        $totalOutstanding = bcsub(
            bcadd((string) $supplier->opening_balance, $invoiceTotal, 2),
            bcadd($payments, $credits, 2),
            2
        );
        if (bccomp($totalOutstanding, '0', 2) < 0) {
            $totalOutstanding = '0.00';
        }

        $openingOutstanding = bcsub($totalOutstanding, $invoiceOutstanding, 2);
        if (bccomp($openingOutstanding, '0', 2) < 0) {
            $openingOutstanding = '0.00';
        }

        return [
            'total_outstanding' => $totalOutstanding,
            'invoice_outstanding' => $invoiceOutstanding,
            'opening_outstanding' => $openingOutstanding,
        ];
    }

    public function post(array $data, $user): SupplierPayment
    {
        return DB::transaction(function () use ($data, $user) {
            Supplier::where('company_id', $user->company_id)
                ->lockForUpdate()->findOrFail($data['supplier_id']);

            $position = $this->position($user->company_id, (int) $data['supplier_id']);
            $openingApplied = (string) ($data['opening_balance_applied'] ?? '0');
            if (bccomp($openingApplied, $position['opening_outstanding'], 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment against the opening payable exceeds its remaining balance.',
                ]);
            }

            $invoiceAllocated = '0.00';
            $lines = [];
            foreach (($data['allocations'] ?? []) as $id => $value) {
                if (! $value || bccomp((string) $value, '0', 2) <= 0) {
                    continue;
                }

                $invoice = SupplierInvoice::where('company_id', $user->company_id)
                    ->where('supplier_id', $data['supplier_id'])
                    ->where('status', 'posted')->lockForUpdate()->findOrFail($id);
                if (bccomp((string) $value, (string) $invoice->balance_amount, 2) > 0) {
                    throw ValidationException::withMessages([
                        'allocations' => "Allocation exceeds {$invoice->document_number} balance.",
                    ]);
                }

                $invoiceAllocated = bcadd($invoiceAllocated, (string) $value, 2);
                $lines[] = [$invoice, (string) $value];
            }

            $amount = (string) $data['amount'];
            $allocated = bcadd($openingApplied, $invoiceAllocated, 2);
            if (bccomp($allocated, $amount, 2) > 0) {
                throw ValidationException::withMessages(['allocations' => 'Allocations cannot exceed payment.']);
            }

            $unapplied = bcsub($amount, $allocated, 2);
            $payment = SupplierPayment::create([
                'company_id' => $user->company_id,
                'supplier_id' => $data['supplier_id'],
                'paid_by' => $user->id,
                'payment_number' => app(DocumentNumberService::class)->next($user->company_id, 'supplier_payment', 'SP'),
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'allocated_amount' => $allocated,
                'opening_balance_applied' => $openingApplied,
                'unapplied_amount' => $unapplied,
                'reference' => $data['reference'] ?? null,
            ]);

            foreach ($lines as [$invoice, $value]) {
                $payment->allocations()->create(['supplier_invoice_id' => $invoice->id, 'amount' => $value]);
                $paid = bcadd((string) $invoice->paid_amount, $value, 2);
                $balance = bcsub((string) $invoice->balance_amount, $value, 2);
                $invoice->update([
                    'paid_amount' => $paid,
                    'balance_amount' => $balance,
                    'payment_status' => bccomp($balance, '0', 2) <= 0 ? 'paid' : 'partially_paid',
                ]);
            }

            $journal = [];
            if (bccomp($allocated, '0', 2) > 0) {
                $journal[] = ['account_code' => '2100', 'debit' => $allocated, 'supplier_id' => $data['supplier_id']];
            }
            if (bccomp($unapplied, '0', 2) > 0) {
                $journal[] = ['account_code' => '1150', 'debit' => $unapplied, 'supplier_id' => $data['supplier_id']];
            }
            $journal[] = [
                'account_code' => $data['payment_method'] === 'cash' ? '1110' : '1120',
                'credit' => $amount,
            ];

            app(JournalPostingService::class)->post(
                $user->company_id,
                $data['payment_date'],
                SupplierPayment::class,
                $payment->id,
                $payment->payment_number,
                "Supplier payment {$payment->payment_number}",
                $journal,
                $user->id
            );

            return $payment;
        });
    }
}
