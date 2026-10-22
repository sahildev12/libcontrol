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
        return User::query()
            ->whereHas('adminProfile', fn ($query) => $query->where('admin_type', Admin::TYPE_CLIENT))
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
