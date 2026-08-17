<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackdatedInvoiceSetting extends Model
{
    protected $guarded = [];

    protected $casts = ['temporary_until' => 'datetime', 'requested_at' => 'datetime'];

    public function activeDays(): int
    {
        return $this->temporary_until?->isFuture() ? (int) $this->temporary_days : 7;
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
