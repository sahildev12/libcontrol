<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddonInstallation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'version',
        'enabled',
        'settings',
        'installed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'settings' => 'array',
            'installed_at' => 'datetime',
        ];
    }
}
