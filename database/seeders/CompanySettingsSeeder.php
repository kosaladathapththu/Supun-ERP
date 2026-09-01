<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('companies')->updateOrInsert(
            ['code' => 'SUPUN'],
            ['name' => 'Fuji Industries', 'legal_name' => 'Fuji Industries', 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );
        $companyId = DB::table('companies')->where('code', 'SUPUN')->value('id');

        $settings = [
            'currency' => ['LKR', 'string'], 'currency_symbol' => ['Rs.', 'string'],
            'date_format' => ['Y-m-d', 'string'], 'timezone' => ['Asia/Colombo', 'string'],
            'invoice_prefix' => ['INV', 'string'], 'receipt_prefix' => ['REC', 'string'],
            'purchase_prefix' => ['PUR', 'string'], 'default_credit_period' => ['30_days', 'string'],
            'stock_cost_method' => ['weighted_average', 'string'], 'session_timeout_minutes' => ['120', 'integer'],
            'company_footer' => ['', 'string'], 'invoice_footer' => ['Thank you for your business.', 'string'],
        ];
        foreach ($settings as $key => [$value, $type]) {
            DB::table('company_settings')->updateOrInsert(
                ['company_id' => $companyId, 'key' => $key],
                ['value' => $value, 'data_type' => $type, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $start = Carbon::create(2026, 1, 1);
        DB::table('financial_years')->updateOrInsert(
            ['company_id' => $companyId, 'name' => 'FY 2026'],
            ['starts_on' => $start, 'ends_on' => $start->copy()->endOfYear(), 'status' => 'open', 'is_current' => true, 'updated_at' => $now, 'created_at' => $now]
        );
        $financialYearId = DB::table('financial_years')->where('company_id', $companyId)->where('name', 'FY 2026')->value('id');
        foreach (range(1, 12) as $month) {
            $periodStart = Carbon::create(2026, $month, 1);
            DB::table('accounting_periods')->updateOrInsert(
                ['financial_year_id' => $financialYearId, 'starts_on' => $periodStart->toDateString()],
                ['name' => $periodStart->format('F Y'), 'ends_on' => $periodStart->copy()->endOfMonth()->toDateString(), 'status' => 'open', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach ([['RET', 'Retail', 'retail'], ['WHO', 'Wholesale', 'wholesale']] as [$code, $name, $tier]) {
            DB::table('customer_types')->updateOrInsert(['company_id' => $companyId, 'code' => $code], ['name' => $name, 'price_tier' => $tier, 'updated_at' => $now, 'created_at' => $now]);
        }
        $retailTypeId = DB::table('customer_types')->where('company_id', $companyId)->where('code', 'RET')->value('id');
        DB::table('customers')->updateOrInsert(
            ['company_id' => $companyId, 'code' => 'WALK-IN'],
            ['customer_type_id' => $retailTypeId, 'name' => 'Walk-in Customer', 'credit_enabled' => false, 'is_walk_in' => true, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );
        foreach ([['PCS', 'Pieces', 0], ['UNIT', 'Units', 0]] as [$code, $name, $places]) {
            DB::table('units')->updateOrInsert(['company_id' => $companyId, 'code' => $code], ['name' => $name, 'decimal_places' => $places, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]);
        }
    }
}
