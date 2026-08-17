<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\DB;

class ControlCenterService
{
    public function syncNotifications(int $company): void
    {
        $low = DB::table('products')->where('company_id', $company)->where('is_active', 1)->whereNull('deleted_at')->whereColumn('current_quantity', '<=', 'reorder_level')->count();
        $ar = DB::table('sales')->where('company_id', $company)->where('status', 'posted')->where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->sum('balance_amount');
        $ap = DB::table('supplier_invoices')->where('company_id', $company)->where('status', 'posted')->where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->sum('balance_amount');
        $pending = ApprovalRequest::where('company_id', $company)->where('status', 'pending')->count();
        foreach ([['low_stock', $low ? 'warning' : 'info', 'Stock attention', "{$low} products are at or below reorder level.", 'reports.inventory', 'low-stock'], ['overdue_ar', $ar > 0 ? 'danger' : 'info', 'Overdue receivables', 'Rs. '.number_format($ar, 2).' is overdue.', 'receivables.aging', 'overdue-ar'], ['overdue_ap', $ap > 0 ? 'warning' : 'info', 'Overdue payables', 'Rs. '.number_format($ap, 2).' is overdue.', 'payables.aging', 'overdue-ap'], ['pending_approvals', $pending ? 'warning' : 'info', 'Pending approvals', "{$pending} requests await review.", 'controls.approvals', 'pending-approvals']] as [$type,$severity,$title,$message,$route,$fingerprint]) {
            SystemNotification::updateOrCreate(['company_id' => $company, 'fingerprint' => $fingerprint], ['type' => $type, 'severity' => $severity, 'title' => $title, 'message' => $message, 'action_url' => route($route), 'read_at' => null]);
        }
    }
}
