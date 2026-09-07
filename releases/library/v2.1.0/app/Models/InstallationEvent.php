<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'license_key_hash',
        'domain',
        'app_url',
        'fingerprint',
        'server_ip',
        'php_version',
        'app_version',
        'is_authorized',
        'first_seen_at',
        'last_seen_at',
        'hit_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_authorized' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'hit_count' => 'integer',
        ];
    }

    public function licensedDeployment(): BelongsTo
    {
        return $this->belongsTo(LicensedDeployment::class, 'license_key_hash', 'license_key_hash');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function recordHeartbeat(string $licenseKeyHash, array $payload, bool $isAuthorized): self
    {
        $now = now();
        $existing = self::query()
            ->where('license_key_hash', $licenseKeyHash)
            ->where('fingerprint', $payload['fingerprint'])
            ->first();

        if ($existing) {
            $existing->fill([
                'domain' => $payload['domain'],
                'app_url' => $payload['app_url'] ?? null,
                'server_ip' => $payload['server_ip'] ?? null,
                'php_version' => $payload['php_version'] ?? null,
                'app_version' => $payload['app_version'] ?? null,
                'is_authorized' => $isAuthorized,
                'last_seen_at' => $now,
                'hit_count' => $existing->hit_count + 1,
            ]);
            $existing->save();

            return $existing;
        }

        return self::query()->create([
            'license_key_hash' => $licenseKeyHash,
            'domain' => $payload['domain'],
            'app_url' => $payload['app_url'] ?? null,
            'fingerprint' => $payload['fingerprint'],
            'server_ip' => $payload['server_ip'] ?? null,
            'php_version' => $payload['php_version'] ?? null,
            'app_version' => $payload['app_version'] ?? null,
            'is_authorized' => $isAuthorized,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
            'hit_count' => 1,
        ]);
    }
}
