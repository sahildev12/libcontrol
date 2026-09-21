<?php

namespace App\Services;

use RuntimeException;

class EnvFileService
{
    public function set(string $key, string $value, ?string $path = null): void
    {
        $path = $path ?? base_path('.env');

        if (! is_file($path)) {
            throw new RuntimeException('.env file not found.');
        }

        $content = (string) file_get_contents($path);
        $line = $key.'='.$this->escapeValue($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content)) {
            $content = (string) preg_replace($pattern, $line, $content);
        } else {
            $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $content);
    }

    private function escapeValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|"|=/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
