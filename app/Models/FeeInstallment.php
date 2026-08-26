<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeInstallment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'seat_booking_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(SeatBooking::class, 'seat_booking_id');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
