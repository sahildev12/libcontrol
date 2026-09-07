<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordPageActivity
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user() || $response->getStatusCode() >= 400) {
            return $response;
        }

        if ($this->shouldSkip($request)) {
            return $response;
        }

        $routeName = $request->route()?->getName() ?: 'unknown';
        $isWrite = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        app(ActivityLogger::class)->record(
            $request->user(),
            $isWrite ? 'page.changed' : 'page.viewed',
            $isWrite
                ? $this->writeDescription($request, $routeName)
                : $this->viewDescription($request, $routeName),
            null,
            $request->user()->branch_id,
            [
                'route' => $routeName,
                'status' => $response->getStatusCode(),
            ],
            $request,
        );

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        if ($request->expectsJson() || $request->ajax()) {
            return true;
        }

        $name = (string) $request->route()?->getName();

        if (in_array($name, [
            'logout',
            'login',
            'login.store',
            'admin.login',
            'admin.login.store',
            'password.email',
            'password.store',
            'notifications.mark-read',
            'notifications.mark-all-read',
            'seats.data',
            'trial-seats.data',
            'seat-assignments.available-seats',
            'trial-seats.available-seats',
            'students.photo',
            'students.id-proof',
            'webhooks.seat-map',
        ], true)) {
            return true;
        }

        if (str_ends_with($name, '.data') || str_contains($name, 'webhooks')) {
            return true;
        }

        if ($request->is('build/*', 'storage/*', 'livewire/*')) {
            return true;
        }

        return false;
    }

    private function viewDescription(Request $request, string $routeName): string
    {
        $page = $this->pageLabel($routeName);

        return "Opened {$page}.";
    }

    private function writeDescription(Request $request, string $routeName): string
    {
        $page = $this->pageLabel($routeName);
        $verb = match ($request->method()) {
            'POST' => 'Submitted a change on',
            'PUT', 'PATCH' => 'Updated',
            'DELETE' => 'Deleted from',
            default => 'Changed',
        };

        return "{$verb} {$page}.";
    }

    private function pageLabel(string $routeName): string
    {
        $base = strtok($routeName, '.') ?: $routeName;

        return match ($base) {
            'dashboard' => 'Dashboard',
            'branch' => 'Branch',
            'halls' => 'Halls',
            'seats' => 'Seats',
            'trial-seats' => 'Trial Seats',
            'students' => 'Students',
            'enquiries' => 'Enquiry',
            'fees' => 'Fee Management',
            'notifications' => 'Notifications',
            'activity-logs' => 'Activity Log',
            'settings' => 'Settings',
            'profile' => 'Profile',
            'seat-assignments' => 'Seat Assignment',
            default => str_replace(['-', '_'], ' ', $base),
        };
    }
}
