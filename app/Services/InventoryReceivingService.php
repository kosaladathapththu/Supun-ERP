<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryReceivingService
{
    public function receive(Product $product, int $companyId, int $locationId, $quantity, $unitCost, string $referenceType, int $referenceId, string $referenceNumber, int $userId): array
    {
        $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
        $qty = (string) $quantity;
        $cost = (string) $unitCost;
        if (bccomp($qty, '0', 4) <= 0 || bccomp($cost, '0', 4) < 0) {
            throw new RuntimeException('Received quantity must be positive and cost cannot be negative.');
        }
        $beforeQty = (string) $product->current_quantity;
        $beforeCost = (string) $product->average_cost;
        $beforeValue = bcmul($beforeQty, $beforeCost, 8);
        $receivedValue = bcmul($qty, $cost, 8);
        $afterQty = bcadd($beforeQty, $qty, 4);
        $afterCost = bccomp($afterQty, '0', 4) === 0 ? '0' : bcdiv(bcadd($beforeValue, $receivedValue, 8), $afterQty, 4);
        $product->update(['current_quantity' => $afterQty, 'average_cost' => $afterCost]);
        $movementId = DB::table('stock_movements')->insertGetId(['company_id' => $companyId, 'product_id' => $product->id, 'stock_location_id' => $locationId, 'created_by' => $userId, 'movement_at' => now(), 'movement_type' => 'purchase_receipt', 'reference_type' => $referenceType, 'reference_id' => $referenceId, 'reference_number' => $referenceNumber, 'quantity_in' => $qty, 'quantity_out' => 0, 'balance_quantity' => $afterQty, 'unit_cost' => $cost, 'stock_value' => bcmul($afterQty, $afterCost, 2), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('inventory_cost_history')->insert(['company_id' => $companyId, 'product_id' => $product->id, 'stock_movement_id' => $movementId, 'quantity_before' => $beforeQty, 'cost_before' => $beforeCost, 'received_quantity' => $qty, 'received_cost' => $cost, 'quantity_after' => $afterQty, 'cost_after' => $afterCost, 'created_at' => now(), 'updated_at' => now()]);

        return ['quantity' => $afterQty, 'average_cost' => $afterCost, 'movement_id' => $movementId];
    }
}
