<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')->where('code', 'SUPUN')->update([
            'name' => 'Fuji Industries',
            'legal_name' => 'Fuji Industries',
            'updated_at' => now(),
        ]);

        $companyId = DB::table('companies')->where('code', 'SUPUN')->value('id');
        $liabilityTypeId = DB::table('account_types')->where('code', 'LIABILITY')->value('id');
        $liabilitiesId = DB::table('accounts')->where('company_id', $companyId)->where('code', '2000')->value('id');

        if ($companyId && $liabilityTypeId && $liabilitiesId) {
            DB::table('accounts')->updateOrInsert(
                ['company_id' => $companyId, 'code' => '2400'],
                [
                    'account_type_id' => $liabilityTypeId,
                    'parent_id' => $liabilitiesId,
                    'name' => 'Non-Current Liabilities',
                    'is_control_account' => false,
                    'allow_manual_posting' => true,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $nonCurrentLiabilitiesId = DB::table('accounts')->where('company_id', $companyId)->where('code', '2400')->value('id');
            DB::table('accounts')->updateOrInsert(
                ['company_id' => $companyId, 'code' => '2410'],
                [
                    'account_type_id' => $liabilityTypeId,
                    'parent_id' => $nonCurrentLiabilitiesId,
                    'name' => 'Long-Term Loans',
                    'is_control_account' => false,
                    'allow_manual_posting' => true,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('code', ['2410', '2400'])->whereIn('name', ['Long-Term Loans', 'Non-Current Liabilities'])->delete();
        DB::table('companies')->where('code', 'SUPUN')->update([
            'name' => 'Camy Global Marcket',
            'legal_name' => 'Camy Global Marcket',
            'updated_at' => now(),
        ]);
    }
};
