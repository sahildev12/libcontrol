<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function adminProfile(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    public function isAnyAdmin(): bool
    {
        return $this->adminProfile !== null;
    }

    /** @deprecated Use isClientAdmin() or isAnyAdmin() explicitly. */
    public function isPlatformAdmin(): bool
    {
        return $this->isClientAdmin();
    }

    public function isDeveloperAdmin(): bool
    {
        return $this->adminProfile?->isDeveloper() ?? false;
    }

    public function isClientAdmin(): bool
    {
        return $this->adminProfile?->isClient() ?? false;
    }

    public function isBranchStaff(): bool
    {
        return $this->branch_id !== null && ! $this->isAnyAdmin();
    }

    public function adminTypeLabel(): ?string
    {
        if ($this->isDeveloperAdmin()) {
            return 'Developer';
        }

        if ($this->isClientAdmin()) {
            return 'Admin';
        }

        if ($this->isBranchStaff()) {
            return 'Branch';
        }

        return null;
    }

    public function sendPasswordResetNotification(mixed $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
