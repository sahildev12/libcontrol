<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GrowthOrder extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_QUOTED = 'quoted';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'branch_id',
        'requested_by',
        'order_type',
        'item_key',
        'item_name',
        'status',
        'priority',
        'message',
        'contact_name',
        'contact_email',
        'contact_phone',
        'library_name',
        'library_code',
        'deployment_domain',
        'deployment_license_key_hash',
        'support_ticket_id',
        'razorpay_payment_id',
        'razorpay_subscription_id',
        'razorpay_payment_link_id',
        'payment_status',
        'amount_paise',
        'monthly_report_url',
        'admin_notes',
        'quoted_at',
        'activated_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quoted_at' => 'datetime',
            'activated_at' => 'datetime',
            'completed_at' => 'datetime',
            'amount_paise' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GrowthOrder $order) {
            if (blank($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_QUOTED,
            self::STATUS_ACTIVE,
            self::STATUS_PAUSED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public function ticketStatus(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => SupportTicket::STATUS_OPEN,
            self::STATUS_COMPLETED => SupportTicket::STATUS_RESOLVED,
            self::STATUS_CANCELLED => SupportTicket::STATUS_CLOSED,
            default => SupportTicket::STATUS_IN_PROGRESS,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_QUOTED => 'Quoted',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'New',
        };
    }
}
