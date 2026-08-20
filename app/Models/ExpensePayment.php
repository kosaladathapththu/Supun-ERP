<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpensePayment extends Model
{
    protected $guarded = [];
    protected $casts = ['payment_date' => 'date', 'amount' => 'decimal:2'];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
