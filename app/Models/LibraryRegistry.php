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
        'library_code',
        'domain',
        'app_url',
        'client_name',
        'student_code_prefix',
        'student_code_padding',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_code_padding' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function upsertFromSyncPayload(array $payload): void
    {
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $code = preg_replace('/\D+/', '', (string) ($meta['library_code'] ?? '')) ?? '';

        if ($code === '') {
            return;
        }

        $domain = LicensedDeployment::normalizeDomain((string) ($payload['domain'] ?? ''));
        if ($domain === '') {
            return;
        }

        $appUrl = trim((string) ($payload['app_url'] ?? ''));
        $clientName = trim((string) ($meta['client_name'] ?? ''));
        $prefix = strtoupper(trim((string) ($meta['student_code_prefix'] ?? '')));
        $padding = max(1, min(6, (int) ($meta['student_code_padding'] ?? 3)));
        $now = now();

        static::query()->updateOrCreate(
            ['library_code' => $code],
            [
                'domain' => $domain,
                'app_url' => $appUrl !== '' ? rtrim($appUrl, '/') : null,
                'client_name' => $clientName !== '' ? $clientName : null,
                'student_code_prefix' => $prefix !== '' ? $prefix : null,
                'student_code_padding' => $padding,
                'last_seen_at' => $now,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function mobileResolverPayload(): array
    {
        $prefix = strtoupper(trim((string) $this->student_code_prefix));
        $padding = max(1, min(6, (int) ($this->student_code_padding ?: 3)));

        return [
            'code' => $this->library_code,
            'api_base_url' => $this->apiBaseUrl(),
            'name' => $this->client_name,
            'student_code_prefix' => $prefix !== '' ? $prefix : null,
            'student_code_padding' => $padding,
            'sample_student_code' => $prefix !== ''
                ? sprintf('%s-%0'.$padding.'d', $prefix, 1)
                : null,
        ];
    }

    public function apiBaseUrl(): string
    {
        if (filled($this->app_url)) {
            return rtrim((string) $this->app_url, '/');
        }

        return 'https://'.$this->domain;
    }
}
