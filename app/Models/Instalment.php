<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instalment extends Model
{
    protected $fillable = [
        'ownership_id', 'instalment_number', 'due_date',
        'amount_kobo', 'status', 'paid_payment_id',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class);
    }

    public function paidPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'paid_payment_id');
    }
}
