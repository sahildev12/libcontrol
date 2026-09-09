<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyGroup extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'guardian_name',
        'phone',
        'email',
        'address',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function primaryStudent(): ?Student
    {
        return $this->students()->where('is_family_primary', true)->first()
            ?? $this->students()->orderBy('id')->first();
    }

    public function hasContact(): bool
    {
        return filled($this->phone) || filled($this->email);
    }
}
