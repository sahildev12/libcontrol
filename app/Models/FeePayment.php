<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'seat_booking_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference',
        'notes',
        'received_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(SeatBooking::class, 'seat_booking_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
