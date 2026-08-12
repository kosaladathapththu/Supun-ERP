<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $grns = DB::table('goods_received_notes as grn')
                ->leftJoin('supplier_invoices as invoice', 'invoice.goods_received_note_id', '=', 'grn.id')
                ->where('grn.status', 'posted')->whereNull('invoice.id')
                ->select('grn.*')->orderBy('grn.id')->get();

            foreach ($grns as $grn) {
                $invoiceDate = substr((string) $grn->received_date, 0, 10);
                $period = DB::table('accounting_periods as period')
                    ->join('financial_years as year', 'year.id', '=', 'period.financial_year_id')
                    ->where('year.company_id', $grn->company_id)
                    ->whereDate('period.starts_on', '<=', $invoiceDate)->whereDate('period.ends_on', '>=', $invoiceDate)
                    ->select('period.id')->first();
                $inventoryAccount = DB::table('accounts')->where('company_id', $grn->company_id)->where('code', '1140')->value('id');
                $payableAccount = DB::table('accounts')->where('company_id', $grn->company_id)->where('code', '2100')->value('id');
                if (! $period || ! $inventoryAccount || ! $payableAccount) {
                    throw new \RuntimeException("Cannot repair {$grn->document_number}: accounting setup is incomplete.");
                }

                $now = now();
                $invoiceId = DB::table('supplier_invoices')->insertGetId([
                    'company_id'=>$grn->company_id, 'supplier_id'=>$grn->supplier_id, 'goods_received_note_id'=>$grn->id,
                    'document_number'=>'PI-LEGACY-GRN-'.$grn->id, 'supplier_invoice_number'=>$grn->supplier_invoice_number,
                    'invoice_date'=>$invoiceDate, 'due_date'=>date('Y-m-d', strtotime($invoiceDate.' +30 days')),
                    'total_amount'=>$grn->total_cost, 'paid_amount'=>0, 'balance_amount'=>$grn->total_cost,
                    'payment_status'=>'unpaid', 'status'=>'posted', 'created_at'=>$now, 'updated_at'=>$now,
                ]);
                $journalId = DB::table('journal_entries')->insertGetId([
                    'company_id'=>$grn->company_id, 'accounting_period_id'=>$period->id, 'created_by'=>$grn->received_by,
                    'journal_number'=>'JV-LEGACY-GRN-'.$grn->id, 'entry_date'=>$invoiceDate,
                    'source_type'=>App\Models\SupplierInvoice::class, 'source_id'=>$invoiceId,
                    'reference_number'=>$grn->document_number, 'description'=>'Legacy payable repair for '.$grn->document_number,
                    'status'=>'posted', 'created_at'=>$now, 'updated_at'=>$now,
                ]);
                DB::table('journal_lines')->insert([
                    ['journal_entry_id'=>$journalId,'account_id'=>$inventoryAccount,'customer_id'=>null,'supplier_id'=>null,'description'=>'Inventory received','debit'=>$grn->total_cost,'credit'=>0,'created_at'=>$now,'updated_at'=>$now],
                    ['journal_entry_id'=>$journalId,'account_id'=>$payableAccount,'customer_id'=>null,'supplier_id'=>$grn->supplier_id,'description'=>'Supplier payable','debit'=>0,'credit'=>$grn->total_cost,'created_at'=>$now,'updated_at'=>$now],
                ]);
            }
        });
    }

    public function down(): void
    {
        // Posted accounting repairs are intentionally not deleted by rollback.
    }
};
