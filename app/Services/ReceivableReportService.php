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
            ->withSum(['sales as total_invoiced' => fn ($query) => $query->where('status', 'posted')], 'grand_total')
            ->withSum(['sales as outstanding_balance' => fn ($query) => $query->where('status', 'posted')], 'balance_amount')
            ->withSum(['receipts as total_received' => fn ($query) => $query->where('status', 'posted')], 'amount')
            ->orderBy('name')->get();

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
            ],
            'selectedCustomer' => $customerId ? $customers->first() : null,
        ];
    }
}
