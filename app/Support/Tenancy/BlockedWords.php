<?php

namespace App\Support\Tenancy;

class BlockedWords
{
    private const LOOKALIKES = ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's'];

    public static function found(string $value): bool
    {
        $words = preg_split('/[^a-z]+/', strtr(strtolower($value), self::LOOKALIKES), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $anywhere = (array) config('libcontrol.trials.blocked_words.anywhere', []);
        $whole = (array) config('libcontrol.trials.blocked_words.whole', []);

        foreach (self::segments($words) as $segment) {
            if (in_array($segment, $whole, true)) {
                return true;
            }

            $squeezed = (string) preg_replace('/(.)\1+/', '$1', $segment);
            foreach ($anywhere as $word) {
                if (str_contains($segment, $word) || str_contains($squeezed, $word)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Each word, plus runs of one- or two-letter pieces joined back up, so "f-u-c-k" or "fu ck"
     * are caught without matching across ordinary word boundaries ("Kota Knowledge").
     *
     * @param  list<string>  $words
     * @return list<string>
     */
    private static function segments(array $words): array
    {
        $segments = $words;
        $run = '';

        foreach ([...$words, ''] as $word) {
            if ($word !== '' && strlen($word) <= 2) {
                $run .= $word;

                continue;
            }

            if (strlen($run) > 2) {
                $segments[] = $run;
            }
            $run = '';
        }

        return $segments;
    }
}
