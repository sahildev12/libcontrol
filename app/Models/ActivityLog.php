<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'actor_type',
        'method',
        'url',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return ucwords(str_replace(['.', '_'], ' ', $this->action));
    }

    public function actorLabel(): string
    {
        return match ($this->actor_type) {
            'admin' => 'Admin',
            'branch' => 'Library staff',
            default => 'System',
        };
    }
}
