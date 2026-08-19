<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    public const TYPE_REGULAR = 'regular';

    public const TYPE_TRIAL = 'trial';

    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'student_code',
        'name',
        'gender',
        'date_of_birth',
        'father_name',
        'preparing_for',
        'phone',
        'email',
        'id_proof_type',
        'id_proof_path',
        'photo_path',
        'address',
        'status',
        'student_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(SeatBooking::class);
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path || ! Storage::disk('public')->exists($this->photo_path)) {
            return null;
        }

        return route('students.photo', $this);
    }

    public function idProofUrl(): ?string
    {
        if (! $this->id_proof_path || ! Storage::disk('local')->exists($this->id_proof_path)) {
            return null;
        }

        return route('students.id-proof', $this);
    }

    public function isTrialStudent(): bool
    {
        return $this->student_type === self::TYPE_TRIAL;
    }

    public function typeLabel(): string
    {
        return $this->student_type === self::TYPE_TRIAL ? 'Trial Student' : 'Regular Student';
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }
}
