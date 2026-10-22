<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAutomationLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'template_key',
        'recipient_email',
        'related_type',
        'related_id',
        'branch_id',
        'meta',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function alreadySent(string $templateKey, string $relatedType, int|string $relatedId): bool
    {
        return static::query()
            ->where('template_key', $templateKey)
            ->where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->exists();
    }
}
