<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        ?User $user,
        string $action,
        string $description,
        ?Model $subject = null,
        ?int $branchId = null,
        array $properties = [],
        ?Request $request = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $user?->id,
            'branch_id' => $branchId ?? $user?->branch_id,
            'actor_type' => $user?->isPlatformAdmin() ? 'admin' : ($user ? 'branch' : 'system'),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'method' => $request?->method(),
            'url' => $request ? \Illuminate\Support\Str::limit($request->fullUrl(), 490, '') : null,
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
