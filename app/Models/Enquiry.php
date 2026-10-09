<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enquiry extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONVERTED = 'converted';

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'followed_up_1' => 'Followed up 1',
        'followed_up_2' => 'Followed up 2',
        'followed_up_3' => 'Followed up 3',
        'converted' => 'Converted',
        'declined' => 'Declined',
        'closed' => 'Closed',
    ];

    /**
     * @var list<string>
     */
    public const FOLLOW_UP_STATUSES = ['followed_up_1', 'followed_up_2', 'followed_up_3'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'phone',
        'email',
        'message',
        'status',
        'follow_up_date',
        'follow_up_note',
        'student_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUSES[$status] ?? ucfirst((string) $status);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }
}
