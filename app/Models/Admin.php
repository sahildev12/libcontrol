<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin extends Model
{
    public const TYPE_DEVELOPER = 'developer';

    public const TYPE_CLIENT = 'client';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'admin_type',
        'tour_completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tour_completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDeveloper(): bool
    {
        return $this->admin_type === self::TYPE_DEVELOPER;
    }

    public function isClient(): bool
    {
        return $this->admin_type === self::TYPE_CLIENT;
    }
}
