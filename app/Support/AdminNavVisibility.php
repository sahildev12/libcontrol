<?php

namespace App\Support;

use App\Models\User;

class AdminNavVisibility
{
    /**
     * @param  array<string, mixed>  $item
     */
    public static function visibleFor(User $user, array $item): bool
    {
        $developerOnly = ($item['developer_admin_only'] ?? false)
            && ! ($item['client_admin_only'] ?? false)
            && ! ($item['branch_staff_only'] ?? false);

        if ($developerOnly) {
            return $user->isDeveloperAdmin();
        }

        if ($user->isDeveloperAdmin()) {
            return (bool) ($item['developer_admin_only'] ?? false);
        }

        $clientOnly = ($item['client_admin_only'] ?? false) && ! ($item['branch_staff_only'] ?? false);

        if ($clientOnly && ! $user->isClientAdmin()) {
            return false;
        }

        if (($item['client_admin_only'] ?? false) || ($item['branch_staff_only'] ?? false)) {
            return $user->isClientAdmin() || $user->isBranchStaff();
        }

        return true;
    }
}
