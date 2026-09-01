<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LicensedDeployment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_name',
        'license_key_hash',
        'allowed_domains',
        'grace_days',
        'active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_domains' => 'array',
            'grace_days' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function installationEvents(): HasMany
    {
        return $this->hasMany(InstallationEvent::class, 'license_key_hash', 'license_key_hash');
    }

    public const PLACEHOLDER_LICENSE_KEY = 'your_license_key_from_phenomit';

    public static function hashKey(string $licenseKey): string
    {
        return hash('sha256', $licenseKey);
    }

    public static function discoveryKeyHash(): string
    {
        return hash('sha256', 'libspace:discovery');
    }

    public static function isPlaceholderLicenseKey(?string $licenseKey): bool
    {
        $licenseKey = trim((string) $licenseKey);

        return $licenseKey === '' || $licenseKey === self::PLACEHOLDER_LICENSE_KEY;
    }

    public static function generateKey(): string
    {
        return 'ls_'.Str::random(40);
    }

    public function matchesKey(string $licenseKey): bool
    {
        return hash_equals($this->license_key_hash, self::hashKey($licenseKey));
    }

    public function allowsDomain(string $domain): bool
    {
        $normalized = self::normalizeDomain($domain);

        foreach ($this->allowed_domains ?? [] as $allowed) {
            if (self::normalizeDomain((string) $allowed) === $normalized) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));

        if (str_contains($domain, '://')) {
            $host = parse_url($domain, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                $domain = $host;
            }
        }

        return rtrim($domain, '/');
    }

    public static function findByLicenseKey(string $licenseKey): ?self
    {
        return self::query()
            ->where('license_key_hash', self::hashKey($licenseKey))
            ->first();
    }
}
