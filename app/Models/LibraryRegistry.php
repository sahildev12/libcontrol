<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryRegistry extends Model
{
    protected $table = 'library_registry';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_code_prefix',
        'domain',
        'app_url',
        'client_name',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function upsertFromSyncPayload(array $payload): void
    {
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $prefix = strtoupper(trim((string) ($meta['student_code_prefix'] ?? '')));

        if ($prefix === '') {
            return;
        }

        $domain = LicensedDeployment::normalizeDomain((string) ($payload['domain'] ?? ''));
        if ($domain === '') {
            return;
        }

        $appUrl = trim((string) ($payload['app_url'] ?? ''));
        $clientName = trim((string) ($meta['client_name'] ?? ''));
        $now = now();

        static::query()->updateOrCreate(
            ['student_code_prefix' => $prefix],
            [
                'domain' => $domain,
                'app_url' => $appUrl !== '' ? rtrim($appUrl, '/') : null,
                'client_name' => $clientName !== '' ? $clientName : null,
                'last_seen_at' => $now,
            ],
        );
    }

    public function apiBaseUrl(): string
    {
        if (filled($this->app_url)) {
            return rtrim((string) $this->app_url, '/');
        }

        return 'https://'.$this->domain;
    }
}
