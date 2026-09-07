<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hall extends Model
{
    /** @use HasFactory<\Database\Factories\HallFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'seat_capacity',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function hasAssignedStudents(): bool
    {
        return SeatBooking::query()
            ->whereHas('seat', fn ($query) => $query->where('hall_id', $this->id))
            ->whereNull('cancelled_at')
            ->exists();
    }

    public function minimumSeatCapacity(): int
    {
        $filled = (int) ($this->filled_seats_count
            ?? $this->seats()
                ->whereHas(
                    'bookings',
                    fn ($query) => $query
                        ->whereNull('cancelled_at')
                        ->where('status', '!=', 'cancelled'),
                )
                ->count());

        return max(1, $filled);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }
}
