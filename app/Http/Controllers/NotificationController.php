<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Services\SupportTicketSyncService;
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

    public function feed(
        Request $request,
        NotificationService $notificationService,
        SupportTicketSyncService $supportTicketSyncService,
    ): JsonResponse {
        $user = $request->user();
        $branchId = $this->optionalActiveBranchId($request);

        if (! config('libcontrol.license_server.enabled')) {
            $supportTicketSyncService->pullUpdates($user);
        }

        return response()->json([
            'unread_count' => $notificationService->unreadCount($branchId, $user),
            'alerts' => $notificationService->alertsForBranch($branchId, $user, 8)->values(),
            'support_ticket_unread' => $notificationService->supportTicketUnreadCount($user),
            'client_support_ticket_updates' => $notificationService->clientSupportTicketUpdateCount($user),
        ]);
    }

    public function markRead(Request $request, NotificationService $notificationService): JsonResponse
    {
        $data = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $notificationService->markKeysRead($user, $data['keys']);

        return response()->json([
            'ok' => true,
            'support_ticket_unread' => $notificationService->supportTicketUnreadCount($user),
        ]);
    }

    public function markAllRead(Request $request, NotificationService $notificationService): JsonResponse
    {
        $user = $request->user();
        $notificationService->markAllRead($this->optionalActiveBranchId($request), $user);

        return response()->json([
            'ok' => true,
            'message' => 'All notifications marked as read.',
            'support_ticket_unread' => $notificationService->supportTicketUnreadCount($user),
        ]);
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
