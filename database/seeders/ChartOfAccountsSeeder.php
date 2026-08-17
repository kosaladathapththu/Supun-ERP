<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['ASSET', 'Assets', 'debit', 'balance_sheet', 10], ['LIABILITY', 'Liabilities', 'credit', 'balance_sheet', 20], ['EQUITY', 'Equity', 'credit', 'balance_sheet', 30], ['REVENUE', 'Revenue', 'credit', 'profit_loss', 40], ['COGS', 'Cost of Sales', 'debit', 'profit_loss', 50], ['EXPENSE', 'Expenses', 'debit', 'profit_loss', 60]] as [$code, $name, $normal, $statement, $order]) {
            DB::table('account_types')->updateOrInsert(['code' => $code], ['name' => $name, 'normal_balance' => $normal, 'statement' => $statement, 'display_order' => $order, 'updated_at' => now(), 'created_at' => now()]);
        }
        $companyId = DB::table('companies')->where('code', 'SUPUN')->value('id');
        $accounts = [
            ['1000', 'Assets', 'ASSET', null, false], ['1100', 'Current Assets', 'ASSET', '1000', false], ['1110', 'Cash in Hand', 'ASSET', '1100', true], ['1120', 'Bank', 'ASSET', '1100', true], ['1130', 'Accounts Receivable', 'ASSET', '1100', true], ['1140', 'Inventory', 'ASSET', '1100', true], ['1150', 'Supplier Advances', 'ASSET', '1100', true],
            ['1200', 'Non-Current Assets', 'ASSET', '1000', false], ['1210', 'Furniture', 'ASSET', '1200', false], ['1220', 'Computers', 'ASSET', '1200', false], ['1230', 'Vehicles', 'ASSET', '1200', false],
            ['2000', 'Liabilities', 'LIABILITY', null, false], ['2100', 'Accounts Payable', 'LIABILITY', '2000', true], ['2150', 'Customer Advances', 'LIABILITY', '2000', true], ['2200', 'Accrued Expenses', 'LIABILITY', '2000', false], ['2300', 'Taxes Payable', 'LIABILITY', '2000', false],
            ['3000', 'Equity', 'EQUITY', null, false], ['3100', 'Owner Capital', 'EQUITY', '3000', false], ['3200', 'Drawings', 'EQUITY', '3000', false], ['3300', 'Retained Earnings', 'EQUITY', '3000', true],
            ['4000', 'Revenue', 'REVENUE', null, false], ['4100', 'Retail Sales', 'REVENUE', '4000', false], ['4200', 'Wholesale Sales', 'REVENUE', '4000', false], ['4300', 'Other Income', 'REVENUE', '4000', false],
            ['5000', 'Cost of Sales', 'COGS', null, false], ['5100', 'Cost of Goods Sold', 'COGS', '5000', false],
            ['6000', 'Expenses', 'EXPENSE', null, false], ['6100', 'Salaries', 'EXPENSE', '6000', false], ['6200', 'Rent', 'EXPENSE', '6000', false], ['6300', 'Electricity', 'EXPENSE', '6000', false], ['6400', 'Transport', 'EXPENSE', '6000', false], ['6500', 'Bank Charges', 'EXPENSE', '6000', false], ['6600', 'Repairs', 'EXPENSE', '6000', false], ['6700', 'Marketing', 'EXPENSE', '6000', false], ['6800', 'Other Expenses', 'EXPENSE', '6000', false],
        ];
        foreach ($accounts as [$code, $name, $type, $parentCode, $control]) {
            $parentId = $parentCode ? DB::table('accounts')->where('company_id', $companyId)->where('code', $parentCode)->value('id') : null;
            DB::table('accounts')->updateOrInsert(
                ['company_id' => $companyId, 'code' => $code],
                ['account_type_id' => DB::table('account_types')->where('code', $type)->value('id'), 'parent_id' => $parentId, 'name' => $name, 'is_control_account' => $control, 'allow_manual_posting' => ! $control, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
