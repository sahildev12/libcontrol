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
        'family_group_id',
        'is_family_primary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_family_primary' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function familyGroup(): BelongsTo
    {
        return $this->belongsTo(FamilyGroup::class);
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

    public function effectivePhone(): ?string
    {
        if (filled($this->phone)) {
            return $this->phone;
        }

        return $this->familyGroup?->phone;
    }

    public function effectiveEmail(): ?string
    {
        if (filled($this->email)) {
            return $this->email;
        }

        return $this->familyGroup?->email;
    }

    public function effectiveGuardianName(): ?string
    {
        if (filled($this->father_name)) {
            return $this->father_name;
        }

        return $this->familyGroup?->guardian_name;
    }

    public function effectiveAddress(): ?string
    {
        if (filled($this->address)) {
            return $this->address;
        }

        return $this->familyGroup?->address;
    }

    public function isInFamily(): bool
    {
        return $this->family_group_id !== null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Student>
     */
    public function linkedSiblings()
    {
        if (! $this->family_group_id) {
            return collect();
        }

        return static::query()
            ->where('family_group_id', $this->family_group_id)
            ->where('id', '!=', $this->id)
            ->orderBy('name')
            ->get();
    }
}
