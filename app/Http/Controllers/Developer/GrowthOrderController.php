<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\GrowthOrder;
use App\Services\Growth\GrowthOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GrowthOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $orders = GrowthOrder::query()
            ->with(['branch:id,name', 'requester:id,name,email'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('developer.growth-orders.index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => [
                GrowthOrder::STATUS_NEW,
                GrowthOrder::STATUS_QUOTED,
                GrowthOrder::STATUS_ACTIVE,
                GrowthOrder::STATUS_PAUSED,
                GrowthOrder::STATUS_COMPLETED,
                GrowthOrder::STATUS_CANCELLED,
            ],
        ]);
    }

    public function show(GrowthOrder $growthOrder): View
    {
        $growthOrder->load(['branch', 'requester']);

        return view('developer.growth-orders.show', [
            'order' => $growthOrder,
        ]);
    }

    public function update(Request $request, GrowthOrder $growthOrder, GrowthOrderService $orderService): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,quoted,active,paused,completed,cancelled'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'monthly_report_url' => ['nullable', 'url', 'max:500'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
        ]);

        $growthOrder->fill([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $growthOrder->admin_notes,
            'monthly_report_url' => $validated['monthly_report_url'] ?? $growthOrder->monthly_report_url,
            'payment_status' => $validated['payment_status'] ?? $growthOrder->payment_status,
        ]);

        if ($validated['status'] === GrowthOrder::STATUS_QUOTED && ! $growthOrder->quoted_at) {
            $growthOrder->quoted_at = now();
        }
        if ($validated['status'] === GrowthOrder::STATUS_ACTIVE && ! $growthOrder->activated_at) {
            $growthOrder->activated_at = now();
        }
        if ($validated['status'] === GrowthOrder::STATUS_COMPLETED && ! $growthOrder->completed_at) {
            $growthOrder->completed_at = now();
        }

        $growthOrder->save();
        $orderService->applyStatusToProfile($growthOrder);

        return back()->with('status', 'Growth order updated.');
    }
}
