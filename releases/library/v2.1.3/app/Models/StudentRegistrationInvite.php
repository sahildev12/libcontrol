<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class StudentRegistrationInvite extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'created_by',
        'token',
        'expires_at',
        'used_at',
        'student_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public static function createForBranch(int $branchId, ?int $createdBy = null, int $ttlHours = 2): self
    {
        return self::query()->create([
            'branch_id' => $branchId,
            'created_by' => $createdBy,
            'token' => Str::random(48),
            'expires_at' => Carbon::now()->addHours($ttlHours),
        ]);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    public function publicUrl(): string
    {
        return route('students.register.show', $this->token);
    }

    public function markUsed(Student $student): void
    {
        $this->forceFill([
            'used_at' => now(),
            'student_id' => $student->id,
        ])->save();
    }
}
