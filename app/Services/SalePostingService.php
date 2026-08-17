<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalePostingService
{
    public function post(array $data, $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $postingDate = isset($data['sale_date']) ? Carbon::parse($data['sale_date'])->startOfDay() : now();
            $lines = [];
            $subtotal = $costTotal = '0';
            foreach ($data['items'] as $i) {
                $p = Product::where('company_id', $user->company_id)->lockForUpdate()->findOrFail($i['product_id']);
                if (bccomp((string) $p->current_quantity, (string) $i['quantity'], 4) < 0) {
                    throw ValidationException::withMessages(['items' => "Insufficient stock for {$p->name}. Available: {$p->current_quantity}"]);
                }$line = bcmul((string) $i['quantity'], (string) $i['unit_price'], 2);
                $cost = bcmul((string) $i['quantity'], (string) $p->average_cost, 2);
                $subtotal = bcadd($subtotal, $line, 2);
                $costTotal = bcadd($costTotal, $cost, 2);
                $lines[] = compact('p', 'i', 'line', 'cost');
            }
            $discount = (string) ($data['discount_amount'] ?? 0);
            $grand = bcsub($subtotal, $discount, 2);
            if (bccomp($grand, '0', 2) < 0) {
                throw ValidationException::withMessages(['discount_amount' => 'Discount cannot exceed subtotal.']);
            }$paid = (string) $data['paid_amount'];
            if (bccomp($paid, $grand, 2) > 0) {
                $paid = $grand;
            }if ($data['payment_type'] === 'cash' && bccomp($paid, $grand, 2) < 0) {
                throw ValidationException::withMessages(['paid_amount' => 'Cash sale must be paid in full.']);
            }
            $number = app(DocumentNumberService::class)->next($user->company_id, $data['payment_type'] === 'cash' ? 'cash_sale' : 'credit_sale', $data['payment_type'] === 'cash' ? 'CS' : 'CR');
            $sale = Sale::create(['company_id' => $user->company_id, 'customer_id' => $data['customer_id'], 'user_id' => $user->id, 'document_number' => $number, 'sale_date' => $postingDate, 'channel' => $data['channel'], 'payment_type' => $data['payment_type'], 'due_date' => $data['due_date'] ?? null, 'subtotal' => $subtotal, 'discount_amount' => $discount, 'grand_total' => $grand, 'paid_amount' => $paid, 'balance_amount' => bcsub($grand, $paid, 2), 'cost_total' => $costTotal, 'gross_profit' => bcsub($grand, $costTotal, 2), 'payment_status' => bccomp($paid, $grand, 2) >= 0 ? 'paid' : (bccomp($paid, '0', 2) > 0 ? 'partially_paid' : 'unpaid'), 'notes' => $data['notes'] ?? null]);
            $location = DB::table('stock_locations')->where('company_id', $user->company_id)->where('is_default', 1)->value('id');
            foreach ($lines as $x) {
                $profit = bcsub($x['line'], $x['cost'], 2);
                $sale->items()->create(['product_id' => $x['p']->id, 'quantity' => $x['i']['quantity'], 'unit_price' => $x['i']['unit_price'], 'unit_cost' => $x['p']->average_cost, 'line_total' => $x['line'], 'cost_total' => $x['cost'], 'gross_profit' => $profit, 'margin_percentage' => bccomp($x['line'], '0', 2) > 0 ? bcmul(bcdiv($profit, $x['line'], 6), '100', 4) : 0]);
                $newQty = bcsub((string) $x['p']->current_quantity, (string) $x['i']['quantity'], 4);
                $x['p']->update(['current_quantity' => $newQty]);
                DB::table('stock_movements')->insert(['company_id' => $user->company_id, 'product_id' => $x['p']->id, 'stock_location_id' => $location, 'created_by' => $user->id, 'movement_at' => $postingDate, 'movement_type' => 'sale', 'reference_type' => Sale::class, 'reference_id' => $sale->id, 'reference_number' => $number, 'quantity_in' => 0, 'quantity_out' => $x['i']['quantity'], 'balance_quantity' => $newQty, 'unit_cost' => $x['p']->average_cost, 'stock_value' => bcmul($newQty, (string) $x['p']->average_cost, 2), 'created_at' => now(), 'updated_at' => now()]);
            }
            if (bccomp($paid, '0', 2) > 0) {
                SalePayment::create(['sale_id' => $sale->id, 'received_by' => $user->id, 'receipt_number' => app(DocumentNumberService::class)->next($user->company_id, 'receipt', 'REC'), 'payment_date' => $postingDate, 'payment_method' => $data['payment_method'], 'amount' => $paid]);
            }
            $journal = [];
            $balance = bcsub($grand, $paid, 2);
            if (bccomp($paid, '0', 2) > 0) {
                $journal[] = ['account_code' => $data['payment_method'] === 'cash' ? '1110' : '1120', 'debit' => $paid];
            }if (bccomp($balance, '0', 2) > 0) {
                $journal[] = ['account_code' => '1130', 'debit' => $balance, 'customer_id' => $sale->customer_id];
            }$journal[] = ['account_code' => $data['channel'] === 'wholesale' ? '4200' : '4100', 'credit' => $grand];
            if (bccomp($costTotal, '0', 2) > 0) {
                $journal[] = ['account_code' => '5100', 'debit' => $costTotal];
                $journal[] = ['account_code' => '1140', 'credit' => $costTotal];
            }app(JournalPostingService::class)->post($user->company_id, $postingDate->toDateString(), Sale::class, $sale->id, $number, "Sale {$number}", $journal, $user->id);

            return $sale;
        });
    }
}
