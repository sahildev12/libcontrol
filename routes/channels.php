<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('branch.{branchId}', function ($user, int $branchId) {
    return (int) $user->branch_id === $branchId;
});
