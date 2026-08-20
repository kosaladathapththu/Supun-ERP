<?php

namespace App\Http\Controllers;

use App\Http\Requests\DirectPurchaseRequest;
use App\Http\Requests\GoodsReceivedNoteRequest;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\DocumentNumberService;
use App\Services\InventoryReceivingService;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceivedNoteController extends Controller
{
    public function directCreate(Request $request)
    {
        $company = $request->user()->company_id;
        $products = Product::where('company_id', $company)->where('is_active', true)->orderBy('name')->get();
        $requestedIds = collect(explode(',', (string) $request->query('products')))
            ->filter(fn ($id) => ctype_digit($id))->map(fn ($id) => (int) $id)->unique()->take(100);

        return view('purchases.direct', [
            'suppliers' => Supplier::where('company_id', $company)->where('is_active', true)->orderBy('name')->get(),
            'products' => $products,
            'selectedProducts' => $products->whereIn('id', $requestedIds)->values(),
            'locations' => DB::table('stock_locations')->where('company_id', $company)->where('is_active', true)->get(),
        ]);
    }

    public function directProductOptions(Request $request)
    {
        return Product::where('company_id', $request->user()->company_id)
            ->where('is_active', true)->orderBy('name')->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => $product->item_code.' — '.$product->name,
                'cost' => $product->average_cost,
                'serial_tracking' => $product->serial_tracking,
            ])->values();
    }

    public function directStore(DirectPurchaseRequest $request, DocumentNumberService $numbers, InventoryReceivingService $inventory)
    {
        $data = $request->validated();
        $products = Product::where('company_id', $request->user()->company_id)->whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
        foreach ($data['items'] as $index => $item) {
            $serials = $this->serials($item['serials'] ?? null);
            if (count($serials) > (int) $item['quantity']) {
                throw ValidationException::withMessages(["items.$index.serials" => 'Serial count cannot exceed the received quantity.']);
            }
        }

        $grn = DB::transaction(function () use ($data, $request, $numbers, $inventory, $products) {
            $company = $request->user()->company_id;
            $number = $numbers->next($company, 'grn', 'GRN', date('Y', strtotime($data['invoice_date'])));
            $grn = GoodsReceivedNote::create([
                'company_id' => $company, 'purchase_order_id' => null, 'supplier_id' => $data['supplier_id'],
                'stock_location_id' => $data['stock_location_id'], 'received_by' => $request->user()->id,
                'document_number' => $number, 'supplier_invoice_number' => $data['supplier_invoice_number'],
                'received_date' => $data['invoice_date'], 'status' => 'posted', 'notes' => $data['notes'] ?? null, 'posted_at' => now(),
            ]);
            $total = '0';
            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];
                $line = bcmul((string) $item['quantity'], (string) $item['unit_cost'], 2);
                $result = $inventory->receive($product, $company, $data['stock_location_id'], $item['quantity'], $item['unit_cost'], GoodsReceivedNote::class, $grn->id, $number, $request->user()->id);
                $grnItem = $grn->items()->create(['purchase_order_item_id' => null, 'product_id' => $product->id, 'quantity' => $item['quantity'], 'unit_cost' => $item['unit_cost'], 'line_total' => $line, 'average_cost_after' => $result['average_cost']]);
                foreach ($this->serials($item['serials'] ?? null) as $serial) {
                    DB::table('product_serial_numbers')->insert(['product_id' => $product->id, 'stock_location_id' => $data['stock_location_id'], 'goods_received_note_item_id' => $grnItem->id, 'serial_number' => $serial, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()]);
                }
                $total = bcadd($total, $line, 2);
            }
            $grn->update(['total_cost' => $total]);
            $invoice = SupplierInvoice::create(['company_id' => $company, 'supplier_id' => $data['supplier_id'], 'goods_received_note_id' => $grn->id, 'document_number' => $numbers->next($company, 'supplier_invoice', 'PI'), 'supplier_invoice_number' => $data['supplier_invoice_number'], 'invoice_date' => $data['invoice_date'], 'due_date' => $data['due_date'] ?? null, 'total_amount' => $total, 'balance_amount' => $total]);
            app(JournalPostingService::class)->post($company, $data['invoice_date'], SupplierInvoice::class, $invoice->id, $invoice->document_number, "Direct inventory purchase {$invoice->document_number}", [['account_code' => '1140', 'debit' => $total], ['account_code' => '2100', 'credit' => $total, 'supplier_id' => $data['supplier_id']]], $request->user()->id);

            return $grn;
        });

        return redirect()->route('grn.show', $grn)->with('success', 'Direct purchase invoice posted. Stock, payable and accounting were updated.');
    }

    public function index(Request $r)
    {
        return view('grn.index', ['grns' => GoodsReceivedNote::with(['supplier', 'purchaseOrder'])->where('company_id', $r->user()->company_id)->latest('received_date')->paginate(15)]);
    }

    public function create(Request $r, $purchase_order)
    {
        $order = PurchaseOrder::with(['items.product'])->where('company_id', $r->user()->company_id)->findOrFail($purchase_order);
        abort_unless(in_array($order->status, ['confirmed', 'partially_received']), 422);
        $locations = DB::table('stock_locations')->where('company_id', $r->user()->company_id)->where('is_active', true)->get();

        return view('grn.form', compact('order', 'locations'));
    }

    public function store(GoodsReceivedNoteRequest $r, $purchase_order, DocumentNumberService $numbers, InventoryReceivingService $inventory)
    {
        $order = PurchaseOrder::with(['items.product'])->where('company_id', $r->user()->company_id)->findOrFail($purchase_order);
        abort_unless(in_array($order->status, ['confirmed', 'partially_received']), 422);
        $receipts = [];
        $errors = [];
        foreach ($order->items as $item) {
            $qty = (string) ($r->input("items.{$item->id}.quantity", 0));
            $remaining = bcsub((string) $item->quantity, (string) $item->received_quantity, 4);
            if (bccomp($qty, '0', 4) > 0) {
                if (bccomp($qty, $remaining, 4) > 0) {
                    $errors[] = "{$item->product->name}: quantity exceeds remaining $remaining";
                }$serials = $this->serials($r->input("items.{$item->id}.serials"));
                if (count($serials) > (int) $qty) {
                    $errors[] = "{$item->product->name}: serial count cannot exceed received quantity";
                }$receipts[] = compact('item', 'qty', 'serials');
            }
        }if (! $receipts) {
            $errors[] = 'Enter at least one received quantity.';
        }if ($errors) {
            throw ValidationException::withMessages(['items' => $errors]);
        }$grn = DB::transaction(function () use ($r, $order, $numbers, $inventory, $receipts) {
        $number = $numbers->next($r->user()->company_id, 'grn', 'GRN', date('Y', strtotime($r->received_date)));
        $g = GoodsReceivedNote::create(['company_id' => $r->user()->company_id, 'purchase_order_id' => $order->id, 'supplier_id' => $order->supplier_id, 'stock_location_id' => $r->stock_location_id, 'received_by' => $r->user()->id, 'document_number' => $number, 'supplier_invoice_number' => $r->supplier_invoice_number, 'received_date' => $r->received_date, 'status' => 'posted', 'notes' => $r->notes, 'posted_at' => now()]);
        $total = '0';
        foreach ($receipts as $x) {
        $item = $x['item'];
        $line = bcmul($x['qty'], (string) $item->unit_cost, 2);
        $result = $inventory->receive($item->product, $r->user()->company_id, $r->stock_location_id, $x['qty'], $item->unit_cost, GoodsReceivedNote::class, $g->id, $number, $r->user()->id);
        $gi = $g->items()->create(['purchase_order_item_id' => $item->id, 'product_id' => $item->product_id, 'quantity' => $x['qty'], 'unit_cost' => $item->unit_cost, 'line_total' => $line, 'average_cost_after' => $result['average_cost']]);
        $item->increment('received_quantity', $x['qty']);
        foreach ($x['serials'] as $serial) {
        DB::table('product_serial_numbers')->insert(['product_id' => $item->product_id, 'stock_location_id' => $r->stock_location_id, 'goods_received_note_item_id' => $gi->id, 'serial_number' => $serial, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()]);
        }$total = bcadd($total, $line, 2);
        }$g->update(['total_cost' => $total]);
        $invoice = SupplierInvoice::create(['company_id' => $r->user()->company_id, 'supplier_id' => $order->supplier_id, 'goods_received_note_id' => $g->id, 'document_number' => $numbers->next($r->user()->company_id, 'supplier_invoice', 'PI'), 'supplier_invoice_number' => $r->supplier_invoice_number, 'invoice_date' => $r->received_date, 'due_date' => date('Y-m-d', strtotime($r->received_date.' +30 days')), 'total_amount' => $total, 'balance_amount' => $total]);
        app(JournalPostingService::class)->post($r->user()->company_id, $r->received_date, SupplierInvoice::class, $invoice->id, $invoice->document_number, "Inventory purchase {$invoice->document_number}", [['account_code' => '1140', 'debit' => $total], ['account_code' => '2100', 'credit' => $total, 'supplier_id' => $order->supplier_id]], $r->user()->id);
        $order->refresh();
        $complete = $order->items()->whereColumn('received_quantity', '<', 'quantity')->doesntExist();
        $order->update(['status' => $complete ? 'received' : 'partially_received']);

        return $g;
        });

        return redirect()->route('grn.show', $grn)->with('success', 'GRN, supplier invoice and accounting entry posted successfully.');
    }

    public function show(Request $r, $grn)
    {
        $grn = GoodsReceivedNote::with(['supplier', 'purchaseOrder', 'items.product'])->where('company_id', $r->user()->company_id)->findOrFail($grn);

        return view('grn.show', compact('grn'));
    }

    private function serials($value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $value)))));
    }
}
