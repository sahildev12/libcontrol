<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_code_prefix',
        'student_code_padding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_code_padding' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'student_code_padding' => config('libspace.defaults.student_code_padding', 3),
        ]);
    }
}
