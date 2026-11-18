<?php

namespace App\Services\EmailAutomation;

use App\Models\Admin;
use App\Models\User;

class OwnerRecipientResolver
{
    /**
     * @return list<string>
     */
    public function clientAdminEmails(): array
    {
        return collect($this->clientAdmins())
            ->pluck('email')
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, email: string}>
     */
    public function clientAdmins(): array
    {
        return User::query()
            ->whereHas('adminProfile', fn ($query) => $query->where('admin_type', Admin::TYPE_CLIENT))
            ->whereNotNull('email')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->map(function (User $user) {
                $email = trim((string) $user->email);

                if ($email === '') {
                    return null;
                }

                return [
                    'id' => (int) $user->id,
                    'email' => $email,
                ];
            })
            ->filter()
            ->unique('email')
            ->values()
            ->all();
    }
}
