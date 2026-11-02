<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subdomain',
        'client_name',
        'owner_name',
        'owner_email',
        'owner_password_hash',
        'owner_email_verified_at',
        'signup_source',
        'database_name',
        'db_username',
        'db_password',
        'plan_tier',
        'is_trial',
        'trial_ends_at',
        'locked_at',
        'max_seats_override',
        'max_halls_override',
        'max_branches_override',
        'active',
        'status',
        'provision_step',
        'provision_error',
        'hosting_subdomain_created',
        'hosting_database_created',
        'signup_ip',
        'notes',
        'provisioned_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'db_password',
        'owner_password_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'db_password' => 'encrypted',
            'max_seats_override' => 'integer',
            'max_halls_override' => 'integer',
            'max_branches_override' => 'integer',
            'active' => 'boolean',
            'is_trial' => 'boolean',
            'hosting_subdomain_created' => 'boolean',
            'hosting_database_created' => 'boolean',
            'owner_email_verified_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'locked_at' => 'datetime',
            'provisioned_at' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        return config('libcontrol.tenancy.landlord_connection') ?: config('database.default');
    }

    public function host(): string
    {
        $base = (string) config('libcontrol.tenancy.base_domain', 'katsindia.com');

        return $this->subdomain.'.'.$base;
    }

    public function url(): string
    {
        return 'https://'.$this->host();
    }

    public static function normalizeSubdomain(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9-]+/', '-')
            ->trim('-')
            ->substr(0, 63)
            ->toString();
    }

    public function planTier(): string
    {
        $tier = (string) ($this->plan_tier ?: config('libcontrol.defaults.plan_tier', 'starter'));

        return array_key_exists($tier, config('libcontrol.plans', [])) ? $tier : 'starter';
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function trialExpired(): bool
    {
        return $this->is_trial && $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }
}
