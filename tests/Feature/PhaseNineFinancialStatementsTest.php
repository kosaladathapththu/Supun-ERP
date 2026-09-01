<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FinancialStatementService;
use App\Services\JournalPostingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseNineFinancialStatementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_statements_derive_balanced_results_from_posted_journals(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::where('email', 'admin@supun-erp.local')->first();
        $journal = app(JournalPostingService::class);
        $date = now()->toDateString();
        $journal->post($u->company_id, $date, self::class, 1, 'CAP-1', 'Owner capital', [['account_code' => '1110', 'debit' => 1000], ['account_code' => '3100', 'credit' => 1000]], $u->id);
        $journal->post($u->company_id, $date, self::class, 2, 'SALE-1', 'Cash sale', [['account_code' => '1110', 'debit' => 100], ['account_code' => '4100', 'credit' => 100]], $u->id);
        $journal->post($u->company_id, $date, self::class, 3, 'EXP-1', 'Cash expense', [['account_code' => '6800', 'debit' => 30], ['account_code' => '1110', 'credit' => 30]], $u->id);
        $service = app(FinancialStatementService::class);
        $pl = $service->profitLoss($u->company_id, $date, $date);
        $this->assertEquals(70, $pl['net_profit']);
        $bs = $service->balanceSheet($u->company_id, $date);
        $this->assertEquals(1070, $bs['assets']);
        $this->assertEquals(1070, $bs['currentAssetsTotal']);
        $this->assertEquals(0, $bs['nonCurrentAssetsTotal']);
        $this->assertEquals(1070, $bs['liabilities_equity']);
        $this->assertEquals(0, $bs['currentLiabilitiesTotal']);
        $this->assertEquals(0, $bs['nonCurrentLiabilitiesTotal']);
        $this->assertEqualsWithDelta(0, $bs['difference'], .001);
        $cash = $service->cashFlow($u->company_id, $date, $date);
        $this->assertEquals(1070, $cash['closingCash']);
        $this->assertEqualsWithDelta(0, $cash['difference'], .001);
    }

    public function test_authorized_user_can_open_every_statement(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::where('email', 'admin@supun-erp.local')->first();
        foreach (['/statements', '/statements/profit-loss', '/statements/balance-sheet', '/statements/cash-flow', '/statements/reconciliation'] as $url) {
            $this->actingAs($u)->get($url)->assertOk();
        }
    }

    public function test_balance_sheet_separates_current_and_non_current_liabilities(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::where('email', 'admin@supun-erp.local')->first();
        $journal = app(JournalPostingService::class);
        $date = now()->toDateString();
        $journal->post($u->company_id, $date, self::class, 10, 'CUR-LIAB', 'Current liability', [['account_code' => '6800', 'debit' => 125], ['account_code' => '2200', 'credit' => 125]], $u->id);
        $journal->post($u->company_id, $date, self::class, 11, 'LONG-LOAN', 'Long-term borrowing', [['account_code' => '1120', 'debit' => 500], ['account_code' => '2410', 'credit' => 500]], $u->id);

        $statement = app(FinancialStatementService::class)->balanceSheet($u->company_id, $date);

        $this->assertEquals(125, $statement['currentLiabilitiesTotal']);
        $this->assertEquals(500, $statement['nonCurrentLiabilitiesTotal']);
        $this->assertEquals(['2200'], $statement['currentLiabilities']->pluck('code')->values()->all());
        $this->assertEquals(['2410'], $statement['nonCurrentLiabilities']->pluck('code')->values()->all());
    }

    public function test_balance_sheet_separates_current_and_non_current_assets(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::where('email', 'admin@supun-erp.local')->first();
        $journal = app(JournalPostingService::class);
        $date = now()->toDateString();
        $journal->post($u->company_id, $date, self::class, 20, 'ASSET-SPLIT', 'Asset classification', [['account_code' => '1110', 'debit' => 200], ['account_code' => '1210', 'debit' => 300], ['account_code' => '3100', 'credit' => 500]], $u->id);

        $statement = app(FinancialStatementService::class)->balanceSheet($u->company_id, $date);

        $this->assertEquals(200, $statement['currentAssetsTotal']);
        $this->assertEquals(300, $statement['nonCurrentAssetsTotal']);
        $this->assertEquals(['1110'], $statement['currentAssets']->pluck('code')->values()->all());
        $this->assertEquals(['1210'], $statement['nonCurrentAssets']->pluck('code')->values()->all());
    }
}
