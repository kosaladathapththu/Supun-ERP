<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesDocumentWorkflowService
{
    public function createQuotation(array $data, $user): Quotation
    {
        return DB::transaction(function () use ($data, $user) {
        $subtotal = '0';
        $lines = [];
        foreach ($data['items'] as $i) {
        $product = Product::where('company_id', $user->company_id)->findOrFail($i['product_id']);
        $line = bcmul((string) $i['quantity'], (string) $i['unit_price'], 2);
        $subtotal = bcadd($subtotal, $line, 2);
        $lines[] = compact('product', 'i', 'line');
        }$discount = (string) ($data['discount_amount'] ?? 0);
        if (bccomp($discount, $subtotal, 2) > 0) {
        throw ValidationException::withMessages(['discount_amount' => 'Discount cannot exceed subtotal.']);
        }$q = Quotation::create(['company_id' => $user->company_id, 'customer_id' => $data['customer_id'], 'created_by' => $user->id, 'document_number' => app(DocumentNumberService::class)->next($user->company_id, 'quotation', 'QT'), 'quotation_date' => $data['quotation_date'], 'valid_until' => $data['valid_until'] ?? null, 'channel' => $data['channel'], 'status' => 'sent', 'subtotal' => $subtotal, 'discount_amount' => $discount, 'total_amount' => bcsub($subtotal, $discount, 2), 'notes' => $data['notes'] ?? null]);
        foreach ($lines as $x) {
        $q->items()->create(['product_id' => $x['product']->id, 'quantity' => $x['i']['quantity'], 'unit_price' => $x['i']['unit_price'], 'line_total' => $x['line']]);
        }

return $q;
        });
    }

    public function convertToOrder(Quotation $quotation, $user): SalesOrder
    {
        return DB::transaction(function () use ($quotation, $user) {
        $quotation = Quotation::with('items')->where('company_id', $user->company_id)->lockForUpdate()->findOrFail($quotation->id);
        if ($quotation->salesOrder) {
            return $quotation->salesOrder;
        }if (! in_array($quotation->status, ['draft', 'sent', 'accepted'])) {
            throw ValidationException::withMessages(['quotation' => 'This quotation cannot be converted.']);
        }if ($quotation->valid_until && $quotation->valid_until->isBefore(today())) {
            throw ValidationException::withMessages(['quotation' => 'This quotation has expired.']);
        }$order = SalesOrder::create(['company_id' => $user->company_id, 'quotation_id' => $quotation->id, 'customer_id' => $quotation->customer_id, 'created_by' => $user->id, 'document_number' => app(DocumentNumberService::class)->next($user->company_id, 'sales_order', 'SO'), 'order_date' => now(), 'expected_date' => now()->addDays(7), 'channel' => $quotation->channel, 'payment_type' => 'credit', 'status' => 'confirmed', 'subtotal' => $quotation->subtotal, 'discount_amount' => $quotation->discount_amount, 'total_amount' => $quotation->total_amount, 'notes' => $quotation->notes]);
        foreach ($quotation->items as $i) {
            $order->items()->create(['product_id' => $i->product_id, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price, 'line_total' => $i->line_total]);
        }$quotation->update(['status' => 'converted']);

        return $order;
        });
    }

    public function deliverAndInvoice(SalesOrder $order, array $data, $user): DeliveryNote
    {
        return DB::transaction(function () use ($order, $data, $user) {
        $order = SalesOrder::with(['items.product', 'customer'])->where('company_id', $user->company_id)->lockForUpdate()->findOrFail($order->id);
        if (! in_array($order->status, ['confirmed', 'partially_delivered'])) {
            throw ValidationException::withMessages(['order' => 'This order has no deliverable balance.']);
        }$items = [];
        $deliverySubtotal = '0';
        foreach ($order->items as $item) {
            $qty = (string) ($data['items'][$item->id]['quantity'] ?? 0);
            if (bccomp($qty, '0', 4) <= 0) {
                continue;
            }$remaining = bcsub((string) $item->quantity, (string) $item->delivered_quantity, 4);
            if (bccomp($qty, $remaining, 4) > 0) {
                throw ValidationException::withMessages(['items' => "{$item->product->name}: quantity exceeds {$remaining} remaining."]);
            }if (bccomp($qty, (string) $item->product->current_quantity, 4) > 0) {
                throw ValidationException::withMessages(['items' => "{$item->product->name}: insufficient stock ({$item->product->current_quantity} available)."]);
            }$line = bcmul($qty, (string) $item->unit_price, 2);
            $deliverySubtotal = bcadd($deliverySubtotal, $line, 2);
            $items[] = ['order_item' => $item, 'product_id' => $item->product_id, 'quantity' => $qty, 'unit_price' => $item->unit_price];
        }if (! $items) {
            throw ValidationException::withMessages(['items' => 'Enter at least one delivery quantity.']);
        }$discount = bccomp((string) $order->subtotal, '0', 2) > 0 ? bcmul((string) $order->discount_amount, bcdiv($deliverySubtotal, (string) $order->subtotal, 8), 2) : '0';
        $sale = app(SalePostingService::class)->post(['customer_id' => $order->customer_id, 'channel' => $order->channel, 'payment_type' => $data['payment_type'], 'due_date' => $data['due_date'] ?? null, 'paid_amount' => $data['paid_amount'], 'payment_method' => $data['payment_method'], 'discount_amount' => $discount, 'notes' => 'Sales order '.$order->document_number.(! empty($data['notes']) ? ' · '.$data['notes'] : ''), 'items' => array_map(fn ($x) => ['product_id' => $x['product_id'], 'quantity' => $x['quantity'], 'unit_price' => $x['unit_price']], $items)], $user);
        $delivery = DeliveryNote::create(['company_id' => $user->company_id, 'sales_order_id' => $order->id, 'sale_id' => $sale->id, 'customer_id' => $order->customer_id, 'created_by' => $user->id, 'document_number' => app(DocumentNumberService::class)->next($user->company_id, 'delivery_note', 'DLN'), 'delivery_date' => now(), 'status' => 'posted', 'delivery_address' => $data['delivery_address'] ?? $order->customer->address, 'notes' => $data['notes'] ?? null]);
        foreach ($items as $x) {
            $delivery->items()->create(['sales_order_item_id' => $x['order_item']->id, 'product_id' => $x['product_id'], 'quantity' => $x['quantity']]);
            $x['order_item']->increment('delivered_quantity', $x['quantity']);
        }$order->refresh();
        $complete = $order->items()->whereColumn('delivered_quantity', '<', 'quantity')->doesntExist();
        $order->update(['status' => $complete ? 'completed' : 'partially_delivered']);

        return $delivery;
        });
    }
}
