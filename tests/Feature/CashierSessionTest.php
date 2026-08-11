<?php

namespace Tests\Feature;

use App\Models\CashierSession;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierSessionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@supun-erp.local')->firstOrFail();
        $this->actingAs($this->admin);
    }

    public function test_cashier_can_open_and_reconcile_a_session(): void
    {
        $this->post(route('cashier-sessions.open'), ['opening_cash' => 5000, 'opening_notes' => 'Morning float'])
            ->assertSessionHasNoErrors();
        $session = CashierSession::firstOrFail();

        $this->post(route('cashier-sessions.close', $session), ['actual_cash' => 4990, 'closing_notes' => 'Rs. 10 short'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cashier_sessions', [
            'id' => $session->id, 'status' => 'closed', 'opening_cash' => 5000,
            'expected_cash' => 5000, 'actual_cash' => 4990, 'variance' => -10,
        ]);
    }

    public function test_cashier_cannot_open_two_sessions_or_close_one_twice(): void
    {
        $this->post(route('cashier-sessions.open'), ['opening_cash' => 1000])->assertSessionHasNoErrors();
        $this->post(route('cashier-sessions.open'), ['opening_cash' => 1000])->assertSessionHasErrors('opening_cash');
        $session = CashierSession::firstOrFail();
        $this->post(route('cashier-sessions.close', $session), ['actual_cash' => 1000])->assertSessionHasNoErrors();
        $this->post(route('cashier-sessions.close', $session), ['actual_cash' => 1000])->assertSessionHasErrors('actual_cash');
        $this->assertDatabaseCount('cashier_sessions', 1);
    }
}
