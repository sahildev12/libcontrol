<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::query()->with(['user:id,name,email', 'branch:id,name']);
        $branchId = $this->optionalActiveBranchId($request);

        if (! $request->user()?->isPlatformAdmin()) {
            $query->where('branch_id', $request->user()->branch_id);
        } elseif ($branchId) {
            $query->where(function ($inner) use ($branchId) {
                $inner->where('branch_id', $branchId)
                    ->orWhere(function ($adminLogs) {
                        $adminLogs->where('actor_type', 'admin')->whereNull('branch_id');
                    });
            });
        }

        $logs = $query
            ->orderByDesc('id')
            ->limit(800)
            ->get()
            ->map(fn (ActivityLog $log) => $this->serializeLog($log));

        $branches = $request->user()?->isPlatformAdmin()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('activity-logs.index', [
            'logs' => $logs,
            'branches' => $branches,
            'isPlatformAdmin' => (bool) $request->user()?->isPlatformAdmin(),
            'scopeLabel' => $this->viewingAllBranches($request)
                ? 'All branches'
                : ($this->optionalActiveBranch($request)?->name ?? 'This branch'),
        ]);
    }

    public function show(Request $request, ActivityLog $activityLog): JsonResponse
    {
        $this->authorizeLog($request, $activityLog);
        $activityLog->load(['user:id,name,email', 'branch:id,name']);

        return response()->json($this->serializeLog($activityLog, true));
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = ActivityLog::query()->whereIn('id', $validated['ids']);

        if (! $request->user()?->isPlatformAdmin()) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $deleted = $query->delete();

        return response()->json([
            'message' => "{$deleted} log(s) deleted.",
            'deleted' => $deleted,
        ]);
    }

    private function authorizeLog(Request $request, ActivityLog $log): void
    {
        if ($log->branch_id) {
            $this->assertCanAccessBranch($request, $log->branch_id);

            return;
        }

        abort_unless($request->user()?->isPlatformAdmin() || $log->user_id === $request->user()?->id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLog(ActivityLog $log, bool $detailed = false): array
    {
        $timezone = config('libspace.timezone', 'Asia/Kolkata');

        $payload = [
            'id' => $log->id,
            'action' => $log->action,
            'action_label' => $log->actionLabel(),
            'description' => $log->description,
            'user_name' => $log->user?->name ?: 'System',
            'actor_type' => $log->actor_type ?: 'system',
            'actor_label' => $log->actorLabel(),
            'branch_id' => $log->branch_id,
            'branch_name' => $log->branch?->name ?: ($log->actor_type === 'admin' ? 'Admin office' : '—'),
            'created_at' => $log->created_at?->timezone($timezone)->format('d M Y, h:i A'),
            'change_summary' => $this->changeSummary($log),
        ];

        if ($detailed) {
            $payload['created_at_full'] = $log->created_at?->timezone($timezone)->format('l, d F Y \a\t h:i A');
            $payload['details'] = $this->friendlyDetails($log);
            $payload['changes'] = $this->changeRows($log);
        }

        return $payload;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function friendlyDetails(ActivityLog $log): array
    {
        $details = [];
        $skip = ['route', 'status', 'ip', 'user_agent', 'method', 'url'];

        foreach ((array) $log->properties as $key => $value) {
            if ($key === 'changes' || in_array((string) $key, $skip, true) || is_array($value)) {
                continue;
            }

            $label = ucwords(str_replace(['_', '-'], ' ', (string) $key));
            $details[] = [
                'label' => $label,
                'value' => is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value,
            ];
        }

        return $details;
    }

    /**
     * @return list<array{label: string, from: string, to: string}>
     */
    private function changeRows(ActivityLog $log): array
    {
        $rows = [];

        foreach ((array) data_get($log->properties, 'changes', []) as $change) {
            if (! is_array($change) || empty($change['label'])) {
                continue;
            }

            $rows[] = [
                'label' => (string) $change['label'],
                'from' => (string) ($change['from'] ?? 'empty'),
                'to' => (string) ($change['to'] ?? 'empty'),
            ];
        }

        return $rows;
    }

    private function changeSummary(ActivityLog $log): string
    {
        $rows = $this->changeRows($log);

        if ($rows === []) {
            return '';
        }

        return collect($rows)
            ->take(3)
            ->map(fn (array $row) => $row['label'].': '.$row['from'].' → '.$row['to'])
            ->implode('; ');
    }
}
