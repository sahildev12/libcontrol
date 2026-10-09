<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    public const STATUS_ENQUIRY = 'enquiry';

    public const STATUS_JOINED = 'joined';

    public const REWARD_NOT_DUE = 'not_due';

    public const REWARD_DUE = 'due';

    public const REWARD_GIVEN = 'given';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'referrer_student_id',
        'enquiry_id',
        'referred_student_id',
        'referred_name',
        'referred_phone',
        'status',
        'reward_status',
        'joined_at',
        'reward_given_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'reward_given_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'referrer_student_id');
    }

    public function referredStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'referred_student_id');
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }
}
