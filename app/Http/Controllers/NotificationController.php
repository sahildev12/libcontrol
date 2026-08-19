<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notificationService): View
    {
        $alerts = $notificationService->alertsForBranch($this->activeBranchId($request));

        return view('notifications.index', [
            'alerts' => $alerts->values(),
        ]);
    }
}
