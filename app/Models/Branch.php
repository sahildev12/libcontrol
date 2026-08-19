<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    /** @use HasFactory<\Database\Factories\BranchFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'student_code_prefix',
        'student_code_padding',
        'expiry_reminder_days',
        'library_open_time',
        'library_close_time',
        'is_open_24_hours',
        'logo_with_text_path',
        'simple_logo_path',
        'favicon_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_code_padding' => 'integer',
            'expiry_reminder_days' => 'integer',
            'is_open_24_hours' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function halls(): HasMany
    {
        return $this->hasMany(Hall::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }
}
