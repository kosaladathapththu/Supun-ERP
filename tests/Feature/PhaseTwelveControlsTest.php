<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\ApprovalRequest;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseTwelveControlsTest extends TestCase
{
    use RefreshDatabase;

    private function users(): array
    {
        $this->seed(DatabaseSeeder::class);
        $requester = User::where('email', 'admin@supun-erp.local')->first();
        $reviewer = User::create(['company_id' => $requester->company_id, 'name' => 'Control Reviewer', 'email' => 'reviewer@example.test', 'password' => Hash::make('Review!2026'), 'is_active' => 1]);
        $reviewer->roles()->attach(DB::table('roles')->where('slug', 'main-admin')->value('id'));

        return [$requester, $reviewer];
    }

    public function test_period_close_and_reopen_require_a_different_approver_and_are_audited(): void
    {
        [$requester,$reviewer] = $this->users();
        $period = AccountingPeriod::whereDate('starts_on', '<=', now())->whereDate('ends_on', '>=', now())->first();
        $this->actingAs($requester)->post("/controls/periods/{$period->id}/request", ['action' => 'close', 'reason' => 'Month-end close'])->assertRedirect();
        $approval = ApprovalRequest::where('status', 'pending')->orderByDesc('id')->first();
        $this->actingAs($requester)->post("/controls/approvals/{$approval->id}/review", ['decision' => 'approved'])->assertSessionHasErrors('approval');
        $this->assertSame('open', $period->fresh()->status);
        $this->actingAs($reviewer)->post("/controls/approvals/{$approval->id}/review", ['decision' => 'approved', 'review_notes' => 'Reconciled'])->assertRedirect();
        $this->assertSame('closed', $period->fresh()->status);
        $this->actingAs($requester)->post("/controls/periods/{$period->id}/request", ['action' => 'reopen', 'reason' => 'Approved correction'])->assertRedirect();
        $reopen = ApprovalRequest::where('status', 'pending')->orderByDesc('id')->first();
        $this->actingAs($reviewer)->post("/controls/approvals/{$reopen->id}/review", ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('open', $period->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['company_id' => $requester->company_id, 'route_name' => 'controls.periods.request']);
        $this->assertDatabaseHas('audit_logs', ['company_id' => $requester->company_id, 'route_name' => 'controls.approvals.review']);
    }

    public function test_control_center_generates_stock_notification_and_pages_open(): void
    {
        [$user] = $this->users();
        $c = $user->company_id;
        $cat = DB::table('product_categories')->insertGetId(['company_id' => $c, 'code' => 'CTRL', 'name' => 'Controls', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Product::create(['company_id' => $c, 'product_category_id' => $cat, 'unit_id' => DB::table('units')->where('code', 'PCS')->value('id'), 'item_code' => 'CTRL-1', 'name' => 'Low Stock', 'average_cost' => 10, 'current_quantity' => 1, 'minimum_stock' => 2, 'reorder_level' => 2, 'warranty_months' => 0, 'serial_tracking' => 0, 'is_active' => 1]);
        foreach (['/controls', '/controls/periods', '/controls/approvals', '/controls/audit'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }$this->assertDatabaseHas('system_notifications', ['company_id' => $c, 'type' => 'low_stock', 'severity' => 'warning']);
    }
}
