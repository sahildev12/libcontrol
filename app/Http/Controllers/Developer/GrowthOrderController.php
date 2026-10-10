<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\GrowthOrder;
use App\Services\Growth\GrowthOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GrowthOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());

        $orders = GrowthOrder::query()
            ->with(['branch:id,name', 'requester:id,name,email'])
            ->when(in_array($status, GrowthOrder::statuses(), true), fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $w) => $w
                    ->where('item_name', 'like', $like)
                    ->orWhere('library_name', 'like', $like)
                    ->orWhere('library_code', 'like', $like)
                    ->orWhere('deployment_domain', 'like', $like)
                    ->orWhere('contact_name', 'like', $like)
                    ->orWhere('contact_email', 'like', $like)
                    ->orWhere('contact_phone', 'like', $like));
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = GrowthOrder::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('developer.growth-orders.index', [
            'orders' => $orders,
            'status' => $status,
            'search' => $search,
            'statuses' => GrowthOrder::statuses(),
            'counts' => $counts,
            'stats' => [
                'total' => (int) $counts->sum(),
                'new' => (int) ($counts[GrowthOrder::STATUS_NEW] ?? 0),
                'in_progress' => (int) (($counts[GrowthOrder::STATUS_QUOTED] ?? 0) + ($counts[GrowthOrder::STATUS_ACTIVE] ?? 0) + ($counts[GrowthOrder::STATUS_PAUSED] ?? 0)),
                'completed' => (int) ($counts[GrowthOrder::STATUS_COMPLETED] ?? 0),
            ],
        ]);
    }

    public function show(GrowthOrder $growthOrder): View
    {
        $growthOrder->load(['branch', 'requester', 'supportTicket']);

        return view('developer.growth-orders.show', [
            'order' => $growthOrder,
            'statuses' => GrowthOrder::statuses(),
        ]);
    }

    public function update(Request $request, GrowthOrder $growthOrder, GrowthOrderService $orderService): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(GrowthOrder::statuses())],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'monthly_report_url' => ['nullable', 'url', 'max:500'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
        ]);

        $growthOrder->fill([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'monthly_report_url' => $validated['monthly_report_url'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
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

        if ($ticket = $growthOrder->supportTicket) {
            $ticket->forceFill([
                'status' => $growthOrder->ticketStatus(),
                'admin_notes' => $growthOrder->admin_notes,
                'read_at' => $ticket->read_at ?? now(),
            ])->save();
        }

        return back()->with('status', 'Service request updated. The library will see the new status next time they open LibControl.');
    }
}
