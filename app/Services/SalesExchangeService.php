<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SalesExchange;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesExchangeService
{
    public function post(Sale $original, array $data, $user): SalesExchange
    {
        return DB::transaction(function () use ($original, $data, $user) {
            $original = Sale::where('company_id', $user->company_id)->lockForUpdate()->findOrFail($original->id);
            if ($original->status !== 'posted') {
                throw ValidationException::withMessages(['sale' => 'Only posted sales can be exchanged.']);
            }

            $return = app(SaleReturnService::class)->post($original, [
                'settlement_type' => 'credit_note',
                'reason' => 'Exchange: '.$data['reason'],
                'items' => $data['returned_items'],
            ], $user);
            $credit = $return->creditNote()->lockForUpdate()->firstOrFail();

            $replacement = app(SalePostingService::class)->post([
                'customer_id' => $original->customer_id,
                'channel' => $original->channel,
                'payment_type' => 'credit',
                'due_date' => now()->addDays(30)->toDateString(),
                'paid_amount' => 0,
                'payment_method' => 'cash',
                'discount_amount' => 0,
                'notes' => 'Replacement for '.$original->document_number,
                'items' => $data['replacement_items'],
            ], $user);

            $applied = bccomp((string) $credit->amount, (string) $replacement->grand_total, 2) > 0
                ? (string) $replacement->grand_total : (string) $credit->amount;
            $balance = bcsub((string) $replacement->grand_total, $applied, 2);
            $credit->update([
                'applied_amount' => $applied,
                'status' => bccomp($applied, (string) $credit->amount, 2) >= 0 ? 'applied' : 'partially_applied',
            ]);
            $replacement->update([
                'paid_amount' => $applied,
                'balance_amount' => $balance,
                'payment_status' => bccomp($balance, '0', 2) <= 0 ? 'paid' : 'partially_paid',
            ]);

            return SalesExchange::create([
                'company_id' => $user->company_id,
                'original_sale_id' => $original->id,
                'sale_return_id' => $return->id,
                'replacement_sale_id' => $replacement->id,
                'credit_note_id' => $credit->id,
                'created_by' => $user->id,
                'document_number' => app(DocumentNumberService::class)->next($user->company_id, 'sales_exchange', 'EX'),
                'exchange_date' => now(),
                'returned_amount' => $return->total_amount,
                'replacement_amount' => $replacement->grand_total,
                'credit_applied' => $applied,
                'balance_due' => $balance,
                'reason' => $data['reason'],
            ]);
        });
    }

    public function void(Sale $sale, string $reason, $user): SaleReturn
    {
        return DB::transaction(function () use ($sale, $reason, $user) {
            $sale = Sale::with('items')->where('company_id', $user->company_id)->lockForUpdate()->findOrFail($sale->id);
            if ($sale->status !== 'posted') {
                throw ValidationException::withMessages(['sale' => 'This invoice is not available for voiding.']);
            }
            if (bccomp((string) $sale->paid_amount, '0', 2) !== 0 || $sale->payments()->exists() || DB::table('customer_receipt_allocations')->where('sale_id', $sale->id)->exists()) {
                throw ValidationException::withMessages(['sale' => 'Paid or allocated invoices cannot be voided. Use a sales return/refund instead.']);
            }
            if (DB::table('sale_returns')->where('sale_id', $sale->id)->exists()) {
                throw ValidationException::withMessages(['sale' => 'An invoice with existing returns cannot be voided.']);
            }
            $items = $sale->items->mapWithKeys(fn ($item) => [$item->id => ['quantity' => $item->quantity, 'condition' => 'resalable']])->all();
            $return = app(SaleReturnService::class)->post($sale, ['settlement_type' => 'credit_note', 'reason' => 'VOID: '.$reason, 'items' => $items], $user);
            $return->update(['return_type' => 'void']);
            $sale->update(['status' => 'voided', 'payment_status' => 'voided', 'balance_amount' => 0, 'reversed_by' => $user->id, 'reversed_at' => now(), 'reversal_reason' => $reason]);

            return $return;
        });
    }
}
