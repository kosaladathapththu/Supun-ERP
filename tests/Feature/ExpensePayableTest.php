<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensePayableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@supun-erp.local')->firstOrFail());
    }

    public function test_partial_expense_payment_leaves_payable_and_later_payment_clears_it(): void
    {
        $account = Account::where('code', '6000')->firstOrFail();
        $this->post(route('expenses.store'), ['expense_date' => '2026-08-20', 'due_date' => '2026-08-31', 'account_id' => $account->id, 'payee' => 'Electricity Board', 'amount' => 500, 'paid_amount' => 200, 'payment_method' => 'cash', 'description' => 'August electricity bill'])
            ->assertSessionHasNoErrors();

        $expense = Expense::where('payee', 'Electricity Board')->firstOrFail();
        $this->assertEquals('200.00', $expense->paid_amount);
        $this->assertEquals('300.00', $expense->balance_amount);
        $this->assertSame('partially_paid', $expense->payment_status);

        $this->post(route('expenses.payment.store', $expense), ['payment_date' => '2026-08-21', 'amount' => 300, 'payment_method' => 'bank_transfer'])
            ->assertSessionHasNoErrors()->assertRedirect(route('expenses.show', $expense));
        $this->assertEquals('0.00', $expense->fresh()->balance_amount);
        $this->assertSame('paid', $expense->fresh()->payment_status);
        $this->assertDatabaseCount('expense_payments', 2);
    }

    public function test_a_recurring_bill_can_link_to_the_previous_bill(): void
    {
        $account = Account::where('code', '6000')->firstOrFail();
        $previous = Expense::create(['company_id' => auth()->user()->company_id, 'account_id' => $account->id, 'created_by' => auth()->id(), 'document_number' => 'EXP-OLD', 'expense_date' => '2026-07-20', 'billing_period' => 'July 2026', 'payee' => 'Electricity Board', 'amount' => 500, 'paid_amount' => 200, 'balance_amount' => 300, 'payment_status' => 'partially_paid', 'payment_method' => 'cash', 'description' => 'Electricity']);

        $this->post(route('expenses.store'), ['expense_date' => '2026-08-20', 'billing_period' => 'August 2026', 'previous_expense_id' => $previous->id, 'account_id' => $account->id, 'payee' => 'Electricity Board', 'amount' => 700, 'paid_amount' => 0, 'description' => 'Electricity'])
            ->assertSessionHasNoErrors();

        $current = Expense::where('billing_period', 'August 2026')->firstOrFail();
        $this->assertSame($previous->id, $current->previous_expense_id);
        $this->get(route('expenses.show', $current))->assertOk()->assertSee('EXP-OLD')->assertSee('Previous:');
        $this->get(route('expenses.show', $previous))->assertOk()->assertSee($current->document_number)->assertSee('Next:');
    }
}
