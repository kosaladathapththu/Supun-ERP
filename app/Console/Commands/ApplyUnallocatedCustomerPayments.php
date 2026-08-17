<?php

namespace App\Console\Commands;

use App\Models\CustomerReceipt;
use App\Models\Sale;
use App\Services\JournalPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyUnallocatedCustomerPayments extends Command
{
    protected $signature = 'receivables:apply-unallocated {--dry-run : Preview without changing records}';
    protected $description = 'Apply posted customer payments to the oldest outstanding credit invoices';

    public function handle(JournalPostingService $journals): int
    {
        $receipts = CustomerReceipt::where('status', 'posted')->where('unapplied_amount', '>', 0)
            ->orderBy('receipt_date')->orderBy('id')->get();

        if ($receipts->isEmpty()) {
            $this->info('No unallocated customer payments found.');
            return self::SUCCESS;
        }

        foreach ($receipts as $receipt) {
            $available = (string) $receipt->unapplied_amount;
            $applications = [];
            $sales = Sale::where('company_id', $receipt->company_id)->where('customer_id', $receipt->customer_id)
                ->where('status', 'posted')->where('payment_type', 'credit')->where('balance_amount', '>', 0)
                ->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderBy('sale_date')->get();

            foreach ($sales as $sale) {
                if (bccomp($available, '0', 2) <= 0) break;
                $amount = bccomp($available, (string) $sale->balance_amount, 2) > 0 ? (string) $sale->balance_amount : $available;
                $applications[] = [$sale, $amount];
                $available = bcsub($available, $amount, 2);
            }

            $applied = bcsub((string) $receipt->unapplied_amount, $available, 2);
            $this->line("{$receipt->receipt_number}: apply Rs. {$applied}; remaining unallocated Rs. {$available}");
            if ($this->option('dry-run') || bccomp($applied, '0', 2) <= 0) continue;

            DB::transaction(function () use ($receipt, $applications, $applied, $available, $journals) {
                $lockedReceipt = CustomerReceipt::lockForUpdate()->findOrFail($receipt->id);
                foreach ($applications as [$sale, $amount]) {
                    $lockedSale = Sale::lockForUpdate()->findOrFail($sale->id);
                    $lockedReceipt->allocations()->create(['sale_id' => $lockedSale->id, 'amount' => $amount]);
                    $paid = bcadd((string) $lockedSale->paid_amount, $amount, 2);
                    $balance = bcsub((string) $lockedSale->balance_amount, $amount, 2);
                    $lockedSale->update(['paid_amount' => $paid, 'balance_amount' => $balance, 'payment_status' => bccomp($balance, '0', 2) <= 0 ? 'paid' : 'partially_paid']);
                }
                $lockedReceipt->update([
                    'allocated_amount' => bcadd((string) $lockedReceipt->allocated_amount, $applied, 2),
                    'unapplied_amount' => $available,
                ]);
                $journals->post($lockedReceipt->company_id, $lockedReceipt->receipt_date->toDateString(), CustomerReceipt::class.'.application', $lockedReceipt->id, $lockedReceipt->receipt_number.'-APPLY', "Apply customer payment {$lockedReceipt->receipt_number}", [
                    ['account_code' => '2150', 'debit' => $applied, 'customer_id' => $lockedReceipt->customer_id],
                    ['account_code' => '1130', 'credit' => $applied, 'customer_id' => $lockedReceipt->customer_id],
                ], $lockedReceipt->received_by);
            });
        }

        $this->info($this->option('dry-run') ? 'Preview complete; no records changed.' : 'Customer payments applied successfully.');
        return self::SUCCESS;
    }
}
