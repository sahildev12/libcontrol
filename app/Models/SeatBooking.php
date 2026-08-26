<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatBooking extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'seat_id',
        'student_id',
        'time_slot',
        'custom_start_time',
        'custom_end_time',
        'fee_type',
        'payment_plan',
        'installment_frequency',
        'fee_amount',
        'amount_paid',
        'fee_paid_at',
        'membership_mode',
        'joining_date',
        'plan_expiry_date',
        'status',
        'trial_start',
        'trial_end',
        'cancelled_at',
        'cancellation_reason',
        'expiry_reminder_sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'plan_expiry_date' => 'date',
            'trial_start' => 'date',
            'trial_end' => 'date',
            'cancelled_at' => 'datetime',
            'expiry_reminder_sent_at' => 'datetime',
            'fee_paid_at' => 'datetime',
            'fee_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(FeeInstallment::class)->orderBy('installment_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }
}
