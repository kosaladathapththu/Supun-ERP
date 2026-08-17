<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\BackdatedInvoiceRequest;
use App\Models\BackdatedInvoiceSetting;
use App\Models\Customer;
use App\Models\Product;
use App\Services\DocumentNumberService;
use App\Services\SalePostingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BackdatedInvoiceController extends Controller
{
    private function setting(Request $r): BackdatedInvoiceSetting
    {
        return BackdatedInvoiceSetting::firstOrCreate(['company_id' => $r->user()->company_id]);
    }

    public function index(Request $r)
    {
        $setting = $this->setting($r)->load('requester');
        $approvalMode = $r->routeIs('admin.backdated-invoices.index');
        $requests = BackdatedInvoiceRequest::with(['requester', 'reviewer', 'sale'])->where('company_id', $r->user()->company_id)->when($approvalMode, fn ($q) => $q->orderByRaw("status = 'pending' DESC"))->latest()->paginate(20);

        return view('backdated-invoices.index', compact('setting', 'requests', 'approvalMode'));
    }

    public function show(Request $r, BackdatedInvoiceRequest $backdatedInvoice)
    {
        $this->owned($r, $backdatedInvoice);
        $approvalMode = $r->routeIs('admin.backdated-invoices.show');
        $backdatedInvoice->load(['requester', 'reviewer', 'sale']);
        $payload = $backdatedInvoice->payload;
        $customer = Customer::where('company_id', $r->user()->company_id)->find($payload['customer_id'] ?? null);
        $productIds = collect($payload['items'] ?? [])->pluck('product_id')->filter();
        $products = Product::where('company_id', $r->user()->company_id)->whereIn('id', $productIds)->get()->keyBy('id');

        return view('backdated-invoices.show', compact('backdatedInvoice', 'payload', 'customer', 'products', 'approvalMode'));
    }

    public function create(Request $r)
    {
        $company = $r->user()->company_id;
        $setting = $this->setting($r);
        $saleMode = $r->query('type') === 'cash' ? 'cash' : 'credit';
        $customers = Customer::with('customerType')->where('company_id', $company)->where('is_active', 1)->when($saleMode === 'credit', fn ($q) => $q->where('is_walk_in', 0))->orderBy('name')->get();

        return view('sales.pos', ['customers' => $customers, 'products' => Product::with(['prices', 'category'])->where('company_id', $company)->where('is_active', 1)->orderBy('name')->get(), 'saleMode' => $saleMode, 'backdated' => true, 'backdateDays' => $setting->activeDays()]);
    }

    public function store(SaleRequest $r)
    {
        $data = $r->validated();
        $days = $this->setting($r)->activeDays();
        $date = now()->parse($r->input('invoice_date'))->startOfDay();
        if (! $date->isPast() || $date->lt(now()->startOfDay()->subDays($days))) {
            throw ValidationException::withMessages(['invoice_date' => "Choose a past date within the allowed {$days}-day window."]);
        }$customer = Customer::where('company_id', $r->user()->company_id)->findOrFail($data['customer_id']);
        if ($data['payment_type'] === 'credit' && $customer->is_walk_in) {
            throw ValidationException::withMessages(['customer_id' => 'A registered customer is required for a backdated credit invoice.']);
        }$data['paid_amount'] = $data['payment_type'] === 'credit' ? 0 : $data['paid_amount'];
        $total = collect($data['items'])->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_price']) - (float) ($data['discount_amount'] ?? 0);
        $record = BackdatedInvoiceRequest::create(['company_id' => $r->user()->company_id, 'requested_by' => $r->user()->id, 'request_number' => app(DocumentNumberService::class)->next($r->user()->company_id, 'backdated_request', 'BDR'), 'invoice_date' => $date, 'payload' => $data, 'total_amount' => $total, 'submitted_at' => now()]);

        return redirect()->route('backdated-invoices.index')->with('success', "{$record->request_number} submitted for CFO approval.");
    }

    public function approve(Request $r, BackdatedInvoiceRequest $backdatedInvoice, SalePostingService $service)
    {
        $this->owned($r, $backdatedInvoice);
        if ($backdatedInvoice->status !== 'pending') {
            throw ValidationException::withMessages(['request' => 'This request has already been reviewed.']);
        }$data = $backdatedInvoice->payload;
        $data['sale_date'] = $backdatedInvoice->invoice_date->toDateString();
        $sale = $service->post($data, $r->user());
        $backdatedInvoice->update(['status' => 'approved', 'reviewed_by' => $r->user()->id, 'reviewed_at' => now(), 'approved_sale_id' => $sale->id, 'review_note' => $r->input('review_note')]);

        return back()->with('success', "{$backdatedInvoice->request_number} approved and posted.");
    }

    public function reject(Request $r, BackdatedInvoiceRequest $backdatedInvoice)
    {
        $this->owned($r, $backdatedInvoice);
        if ($backdatedInvoice->status !== 'pending') {
            throw ValidationException::withMessages(['request' => 'This request has already been reviewed.']);
        }$backdatedInvoice->update(['status' => 'rejected', 'reviewed_by' => $r->user()->id, 'reviewed_at' => now(), 'review_note' => $r->input('review_note')]);

        return back()->with('success', 'Request rejected.');
    }

    public function requestWindow(Request $r)
    {
        $data = $r->validate(['days' => 'required|integer|min:8|max:365']);
        $this->setting($r)->update(['requested_days' => $data['days'], 'requested_by' => $r->user()->id, 'requested_at' => now()]);

        return back()->with('success', "Request for {$data['days']} days sent to the CFO.");
    }

    public function updateWindow(Request $r)
    {
        $data = $r->validate(['days' => 'required|integer|min:8|max:365']);
        $setting = $this->setting($r);
        $setting->update(['temporary_days' => $data['days'], 'temporary_until' => now()->addHours(24), 'set_by' => $r->user()->id, 'requested_days' => null, 'requested_by' => null, 'requested_at' => null]);

        return back()->with('success', "Backdate window changed to {$data['days']} days for 24 hours.");
    }

    public function rejectWindow(Request $r)
    {
        $this->setting($r)->update(['requested_days' => null, 'requested_by' => null, 'requested_at' => null]);

        return back()->with('success', 'Date-range request rejected.');
    }

    private function owned(Request $r, BackdatedInvoiceRequest $x): void
    {
        abort_unless($x->company_id === $r->user()->company_id, 404);
    }
}
