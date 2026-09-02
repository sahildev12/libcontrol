<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'subdomain',
        'client_name',
        'database_name',
        'plan_tier',
        'max_seats_override',
        'max_halls_override',
        'max_branches_override',
        'active',
        'notes',
        'provisioned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_seats_override' => 'integer',
            'max_halls_override' => 'integer',
            'max_branches_override' => 'integer',
            'active' => 'boolean',
            'provisioned_at' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        return config('libspace.tenancy.landlord_connection') ?: config('database.default');
    }

    public function host(): string
    {
        $base = (string) config('libspace.tenancy.base_domain', 'phenomit.com');

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
        $tier = (string) ($this->plan_tier ?: config('libspace.defaults.plan_tier', 'starter'));

        return array_key_exists($tier, config('libspace.plans', [])) ? $tier : 'starter';
    }
}
