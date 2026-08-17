<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerReceipt;

class ReceivableReportService
{
    public function build(int $companyId, ?int $customerId = null): array
    {
        $customers = Customer::where('company_id', $companyId)
            ->registered()->where('is_active', 1)
            ->when($customerId, fn ($query) => $query->whereKey($customerId))
            ->withSum(['sales as total_invoiced' => fn ($query) => $query->where('status', 'posted')->where('payment_type', 'credit')], 'grand_total')
            ->withSum(['sales as outstanding_balance' => fn ($query) => $query->where('status', 'posted')->where('payment_type', 'credit')], 'balance_amount')
            ->withSum(['sales as total_received' => fn ($query) => $query->where('status', 'posted')->where('payment_type', 'credit')], 'paid_amount')
            ->withSum(['receipts as available_advance' => fn ($query) => $query->where('status', 'posted')], 'unapplied_amount')
            ->orderBy('name')->get();

        $customers->each(function ($customer) {
            $customer->current_receivable = max(0, (float) $customer->outstanding_balance - (float) $customer->available_advance);
        });

        $receipts = CustomerReceipt::with('customer')
            ->where('company_id', $companyId)->where('status', 'posted')
            ->whereHas('customer', fn ($query) => $query->registered())
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->latest('receipt_date')->latest('id')->get();

        return [
            'customers' => $customers,
            'receipts' => $receipts,
            'totals' => [
                'invoiced' => (float) $customers->sum('total_invoiced'),
                'received' => (float) $customers->sum('total_received'),
                'outstanding' => (float) $customers->sum('outstanding_balance'),
                'advances' => (float) $customers->sum('available_advance'),
                'current_receivable' => (float) $customers->sum('current_receivable'),
            ],
            'selectedCustomer' => $customerId ? $customers->first() : null,
        ];
    }
}
