<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notificationService): View
    {
        $alerts = $notificationService->alertsForBranch(
            $this->optionalActiveBranchId($request),
            $request->user(),
        );

        return view('notifications.index', [
            'alerts' => $alerts->values(),
        ]);
    }

    public function markRead(Request $request, NotificationService $notificationService): JsonResponse
    {
        $data = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string', 'max:120'],
        ]);

        $notificationService->markKeysRead($request->user(), $data['keys']);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request, NotificationService $notificationService): JsonResponse
    {
        $notificationService->markAllRead($this->optionalActiveBranchId($request), $request->user());

        return response()->json(['ok' => true, 'message' => 'All notifications marked as read.']);
    }

    public function bulkDestroy(Request $request, NotificationService $notificationService): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'max:120'],
        ]);

        $notificationService->dismissKeys($request->user(), $data['ids']);

        return response()->json([
            'ok' => true,
            'message' => count($data['ids']).' notification(s) removed.',
            'deleted' => count($data['ids']),
        ]);
    }
}
