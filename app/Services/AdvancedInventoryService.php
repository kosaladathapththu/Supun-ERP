<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use App\Models\StockLocation;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvancedInventoryService
{
    public function locationQuantity(int $company, int $location, int $product): string
    {
        return (string) DB::table('stock_movements')->where(['company_id' => $company, 'stock_location_id' => $location, 'product_id' => $product])->selectRaw('COALESCE(SUM(quantity_in-quantity_out),0) qty')->value('qty');
    }

    public function transfer(array $data, $user): StockTransfer
    {
        return DB::transaction(function () use ($data, $user) {
        if ((int) $data['from_location_id'] === (int) $data['to_location_id']) {
        throw ValidationException::withMessages(['to_location_id' => 'Destination must differ from source.']);
        }$number = app(DocumentNumberService::class)->next($user->company_id, 'stock_transfer', 'ST');
        $transfer = StockTransfer::create(['company_id' => $user->company_id, 'from_location_id' => $data['from_location_id'], 'to_location_id' => $data['to_location_id'], 'created_by' => $user->id, 'document_number' => $number, 'transfer_date' => now(), 'status' => 'posted', 'notes' => $data['notes'] ?? null]);
        $count = 0;
        foreach ($data['items'] as $i) {
        $qty = (string) ($i['quantity'] ?? 0);
        if (bccomp($qty, '0', 4) <= 0) {
        continue;
        }$p = Product::where('company_id', $user->company_id)->lockForUpdate()->findOrFail($i['product_id']);
        $available = $this->locationQuantity($user->company_id, $data['from_location_id'], $p->id);
        if (bccomp($qty, $available, 4) > 0) {
        throw ValidationException::withMessages(['items' => "{$p->name}: source location has only {$available}."]);
        }$transfer->items()->create(['product_id' => $p->id, 'quantity' => $qty, 'unit_cost' => $p->average_cost]);
        foreach ([[$data['from_location_id'], 0, $qty, 'transfer_out'], [$data['to_location_id'], $qty, 0, 'transfer_in']] as [$location,$in,$out,$type]) {
        DB::table('stock_movements')->insert(['company_id' => $user->company_id, 'product_id' => $p->id, 'stock_location_id' => $location, 'created_by' => $user->id, 'movement_at' => now(), 'movement_type' => $type, 'reference_type' => StockTransfer::class, 'reference_id' => $transfer->id, 'reference_number' => $number, 'quantity_in' => $in, 'quantity_out' => $out, 'balance_quantity' => $p->current_quantity, 'unit_cost' => $p->average_cost, 'stock_value' => bcmul((string) $p->current_quantity, (string) $p->average_cost, 2), 'notes' => $data['notes'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
        }$count++;
        }if (! $count) {
        throw ValidationException::withMessages(['items' => 'Enter at least one transfer quantity.']);
        }

return $transfer;
        });
    }

    public function adjust(array $data, $user): StockAdjustment
    {
        return DB::transaction(function () use ($data, $user) {
        $number = app(DocumentNumberService::class)->next($user->company_id, 'stock_adjustment', 'ADJ');
        $adjustment = StockAdjustment::create(['company_id' => $user->company_id, 'stock_location_id' => $data['stock_location_id'], 'created_by' => $user->id, 'document_number' => $number, 'adjustment_date' => now(), 'adjustment_type' => $data['adjustment_type'], 'status' => 'posted', 'reason' => $data['reason']]);
        $total = '0';
        $count = 0;
        foreach ($data['items'] as $i) {
            $change = (string) ($i['quantity_change'] ?? 0);
            if (bccomp($change, '0', 4) === 0) {
                continue;
            }$p = Product::where('company_id', $user->company_id)->lockForUpdate()->findOrFail($i['product_id']);
            $newQty = bcadd((string) $p->current_quantity, $change, 4);
            if (bccomp($newQty, '0', 4) < 0) {
                throw ValidationException::withMessages(['items' => "{$p->name}: adjustment would make company stock negative."]);
            }if (bccomp($change, '0', 4) < 0) {
                $locationQty = $this->locationQuantity($user->company_id, $data['stock_location_id'], $p->id);
                if (bccomp((string) abs((float) $change), $locationQty, 4) > 0) {
                    throw ValidationException::withMessages(['items' => "{$p->name}: location has only {$locationQty}."]);
                }
            }$value = bcmul($change, (string) $p->average_cost, 2);
            $adjustment->items()->create(['product_id' => $p->id, 'quantity_change' => $change, 'unit_cost' => $p->average_cost, 'value_change' => $value]);
            $p->update(['current_quantity' => $newQty]);
            DB::table('stock_movements')->insert(['company_id' => $user->company_id, 'product_id' => $p->id, 'stock_location_id' => $data['stock_location_id'], 'created_by' => $user->id, 'movement_at' => now(), 'movement_type' => $data['adjustment_type'] === 'damaged' ? 'damaged_stock' : 'adjustment', 'reference_type' => StockAdjustment::class, 'reference_id' => $adjustment->id, 'reference_number' => $number, 'quantity_in' => bccomp($change, '0', 4) > 0 ? $change : 0, 'quantity_out' => bccomp($change, '0', 4) < 0 ? abs((float) $change) : 0, 'balance_quantity' => $newQty, 'unit_cost' => $p->average_cost, 'stock_value' => bcmul($newQty, (string) $p->average_cost, 2), 'notes' => $data['reason'], 'created_at' => now(), 'updated_at' => now()]);
            $total = bcadd($total, $value, 2);
            $count++;
        }if (! $count) {
            throw ValidationException::withMessages(['items' => 'Enter at least one non-zero adjustment.']);
        }$adjustment->update(['total_value' => $total]);
        if (bccomp($total, '0', 2) > 0) {
            app(JournalPostingService::class)->post($user->company_id, now()->toDateString(), StockAdjustment::class, $adjustment->id, $number, "Inventory gain {$number}", [['account_code' => '1140', 'debit' => $total], ['account_code' => '4300', 'credit' => $total]], $user->id);
        } elseif (bccomp($total, '0', 2) < 0) {
            $loss = (string) abs((float) $total);
            app(JournalPostingService::class)->post($user->company_id, now()->toDateString(), StockAdjustment::class, $adjustment->id, $number, "Inventory loss {$number}", [['account_code' => '6800', 'debit' => $loss], ['account_code' => '1140', 'credit' => $loss]], $user->id);
        }

return $adjustment;
        });
    }

    public function startCount(int $location, ?string $notes, $user): StockCount
    {
        return DB::transaction(function () use ($location, $notes, $user) {
        StockLocation::where('company_id', $user->company_id)->findOrFail($location);
        $count = StockCount::create(['company_id' => $user->company_id, 'stock_location_id' => $location, 'created_by' => $user->id, 'document_number' => app(DocumentNumberService::class)->next($user->company_id, 'stock_count', 'SC'), 'count_date' => now(), 'status' => 'draft', 'notes' => $notes]);
        foreach (Product::where('company_id', $user->company_id)->where('is_active', 1)->get() as $p) {
            $count->items()->create(['product_id' => $p->id, 'system_quantity' => $this->locationQuantity($user->company_id, $location, $p->id)]);
        }

return $count;
        });
    }

    public function postCount(StockCount $count, array $quantities, $user): StockCount
    {
        return DB::transaction(function () use ($count, $quantities, $user) {
        $count = StockCount::with('items.product')->where('company_id', $user->company_id)->where('status', 'draft')->lockForUpdate()->findOrFail($count->id);
        $items = [];
        foreach ($count->items as $i) {
            if (! array_key_exists($i->id, $quantities)) {
                throw ValidationException::withMessages(['items' => "Enter counted quantity for {$i->product->name}."]);
            }$counted = (string) $quantities[$i->id];
            $variance = bcsub($counted, (string) $i->system_quantity, 4);
            $i->update(['counted_quantity' => $counted, 'variance_quantity' => $variance]);
            $items[] = ['product_id' => $i->product_id, 'quantity_change' => $variance];
        }if (collect($items)->contains(fn ($i) => bccomp((string) $i['quantity_change'], '0', 4) !== 0)) {
            $this->adjust(['stock_location_id' => $count->stock_location_id, 'adjustment_type' => 'stock_count', 'reason' => 'Stock count '.$count->document_number, 'items' => $items], $user);
        }$count->update(['status' => 'posted', 'posted_by' => $user->id, 'posted_at' => now()]);

        return $count;
        });
    }
}
