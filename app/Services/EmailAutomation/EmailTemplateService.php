<?php

namespace App\Services\EmailAutomation;

use App\Models\PlatformSetting;
use App\Support\EmailTemplateCatalog;
use Illuminate\Validation\ValidationException;

class EmailTemplateService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForSettings(?PlatformSetting $settings = null): array
    {
        $settings ??= PlatformSetting::current();
        $overrides = $this->overrides($settings);

        return collect(EmailTemplateCatalog::all())
            ->map(function (array $template, string $key) use ($overrides) {
                $override = $overrides[$key] ?? null;
                $customized = is_array($override)
                    && (filled($override['subject'] ?? null) || filled($override['body'] ?? null) || filled($override['headline'] ?? null));

                return [
                    'key' => $key,
                    'label' => $template['label'],
                    'group' => $template['group'],
                    'description' => $template['description'],
                    'subject' => filled($override['subject'] ?? null) ? (string) $override['subject'] : $template['subject'],
                    'body' => filled($override['body'] ?? null) ? (string) $override['body'] : $template['body'],
                    'headline' => array_key_exists('headline', (array) $override) && $override['headline'] !== null
                        ? (string) $override['headline']
                        : $template['headline'],
                    'placeholders' => $template['placeholders'],
                    'customized' => $customized,
                    'default_subject' => $template['subject'],
                    'default_body' => $template['body'],
                    'default_headline' => $template['headline'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string|null>  $vars
     * @return array{subject: string, body: string, headline: ?string, customized: bool}
     */
    public function resolve(string $key, array $vars = [], ?PlatformSetting $settings = null): array
    {
        $template = EmailTemplateCatalog::get($key);
        if (! $template) {
            throw ValidationException::withMessages(['template' => 'Unknown email template.']);
        }

        $settings ??= PlatformSetting::current();
        $override = $this->overrides($settings)[$key] ?? null;
        $customized = is_array($override)
            && (filled($override['subject'] ?? null) || filled($override['body'] ?? null) || filled($override['headline'] ?? null));

        $subject = filled($override['subject'] ?? null) ? (string) $override['subject'] : $template['subject'];
        $body = filled($override['body'] ?? null) ? (string) $override['body'] : $template['body'];
        $headline = array_key_exists('headline', (array) $override) && $override['headline'] !== null
            ? (string) $override['headline']
            : $template['headline'];

        return [
            'subject' => $this->replace($subject, $vars),
            'body' => $this->replace($body, $vars),
            'headline' => $headline !== null ? $this->replace($headline, $vars) : null,
            'customized' => $customized,
        ];
    }

    /**
     * @param  array{subject?: string|null, body?: string|null, headline?: string|null}  $payload
     */
    public function save(string $key, array $payload, ?PlatformSetting $settings = null): PlatformSetting
    {
        if (! EmailTemplateCatalog::get($key)) {
            throw ValidationException::withMessages(['template' => 'Unknown email template.']);
        }

        $settings ??= PlatformSetting::current();
        $overrides = $this->overrides($settings);

        $subject = trim((string) ($payload['subject'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        $headline = array_key_exists('headline', $payload)
            ? trim((string) ($payload['headline'] ?? ''))
            : null;

        if ($subject === '' || $body === '') {
            throw ValidationException::withMessages([
                'subject' => $subject === '' ? 'Subject is required.' : null,
                'body' => $body === '' ? 'Body is required.' : null,
            ]);
        }

        $overrides[$key] = array_filter([
            'subject' => $subject,
            'body' => $body,
            'headline' => $headline !== null && $headline !== '' ? $headline : null,
        ], fn ($value) => $value !== null);

        $settings->forceFill(['email_template_overrides' => $overrides])->save();

        return $settings->fresh();
    }

    public function reset(string $key, ?PlatformSetting $settings = null): PlatformSetting
    {
        if (! EmailTemplateCatalog::get($key)) {
            throw ValidationException::withMessages(['template' => 'Unknown email template.']);
        }

        $settings ??= PlatformSetting::current();
        $overrides = $this->overrides($settings);
        unset($overrides[$key]);
        $settings->forceFill(['email_template_overrides' => $overrides === [] ? null : $overrides])->save();

        return $settings->fresh();
    }

    /**
     * @param  array<string, string|null>  $vars
     */
    public function replace(string $text, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $key => $value) {
            $replacements['{'.$key.'}'] = (string) ($value ?? '');
        }

        return strtr($text, $replacements);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function overrides(PlatformSetting $settings): array
    {
        $raw = $settings->email_template_overrides;

        return is_array($raw) ? $raw : [];
    }
}
