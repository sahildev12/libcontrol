<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    public const PURPOSE_SOFTWARE_PLAN = 'software_plan';

    public const PURPOSE_GROWTH_PACKAGE = 'growth_package';

    public const PURPOSE_GROWTH_SERVICE = 'growth_service';

    public const STATUS_CREATED = 'created';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'merchant_order_id',
        'gateway',
        'purpose',
        'item_key',
        'item_name',
        'billing_cycle',
        'amount_paise',
        'status',
        'gateway_order_id',
        'gateway_transaction_id',
        'payment_mode',
        'error_code',
        'licensed_deployment_id',
        'deployment_domain',
        'library_code',
        'library_name',
        'growth_order_uuid',
        'payer_name',
        'payer_email',
        'payer_phone',
        'return_url',
        'paid_at',
        'fulfilled_at',
        'last_checked_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_paise' => 'integer',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (blank($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }

            if (blank($payment->merchant_order_id)) {
                $payment->merchant_order_id = 'LC-'.now()->format('ymdHis').'-'.Str::upper(Str::random(10));
            }
        });
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(LicensedDeployment::class, 'licensed_deployment_id');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function amountLabel(): string
    {
        $rupees = $this->amount_paise / 100;

        return '₹'.number_format($rupees, fmod($rupees, 1.0) === 0.0 ? 0 : 2);
    }

    public function purposeLabel(): string
    {
        return match ($this->purpose) {
            self::PURPOSE_SOFTWARE_PLAN => 'LibControl subscription',
            self::PURPOSE_GROWTH_PACKAGE => 'Growth package',
            self::PURPOSE_GROWTH_SERVICE => 'Growth service',
            default => ucfirst(str_replace('_', ' ', (string) $this->purpose)),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'Paid',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_PENDING => 'Pending',
            default => 'Started',
        };
    }
}
