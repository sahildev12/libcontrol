<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHallRequest;
use App\Http\Requests\UpdateHallRequest;
use App\Models\Branch;
use App\Models\Hall;
use App\Services\HallSeatGenerator;
use App\Services\SeatMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HallController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->activeBranchId($request);
        $branch = $this->activeBranch($request);

        $halls = Hall::query()
            ->where('branch_id', $branchId)
            ->with('branch:id,name')
            ->withCount([
                'seats',
                'seats as filled_seats_count' => fn ($query) => $query->whereHas(
                    'bookings',
                    fn ($bookingQuery) => $bookingQuery
                        ->whereNull('cancelled_at')
                        ->where('status', '!=', 'cancelled'),
                ),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Hall $hall) => [
                'id' => $hall->id,
                'branch_id' => $hall->branch_id,
                'branch_name' => $hall->branch?->name,
                'name' => $hall->name,
                'description' => $hall->description,
                'seat_capacity' => $hall->seat_capacity,
                'filled_seats_count' => $hall->filled_seats_count,
                'has_assigned_students' => $hall->hasAssignedStudents(),
                'min_seat_capacity' => $hall->minimumSeatCapacity(),
                'created_at' => $hall->created_at?->format('M d, Y'),
            ]);

        $branches = Branch::query()
            ->where('id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $branchName = $branch->name;

        return view('halls.index', compact('halls', 'branches', 'branchName'));
    }

    public function show(Request $request, Hall $hall): JsonResponse
    {
        $this->authorizeHall($request, $hall);

        $hall->loadCount($this->hallCountRelations());

        return response()->json([
            'id' => $hall->id,
            'name' => $hall->name,
            'description' => $hall->description,
            'seat_capacity' => $hall->seat_capacity,
            'filled_seats_count' => $hall->filled_seats_count,
            'has_assigned_students' => $hall->hasAssignedStudents(),
            'min_seat_capacity' => $hall->minimumSeatCapacity(),
            'created_at' => $hall->created_at?->format('M d, Y h:i A'),
            'updated_at' => $hall->updated_at?->format('M d, Y h:i A'),
        ]);
    }

    public function store(StoreHallRequest $request, HallSeatGenerator $hallSeatGenerator, SeatMapService $seatMapService): JsonResponse|RedirectResponse
    {
        $hall = Hall::create($request->validated());

        $hallSeatGenerator->generate($hall);
        $hall->load(['branch:id,name'])->loadCount($this->hallCountRelations());

        $seatMapService->broadcastForBranch($this->activeBranchId($request));

        $payload = [
            'message' => "Hall \"{$hall->name}\" created.",
            'hall' => $this->serializeHall($hall),
        ];

        return $request->wantsJson()
            ? response()->json($payload, 201)
            : redirect()->route('halls.index')->with('status', $payload['message']);
    }

    public function update(UpdateHallRequest $request, Hall $hall, HallSeatGenerator $hallSeatGenerator, SeatMapService $seatMapService): JsonResponse|RedirectResponse
    {
        $this->authorizeHall($request, $hall);

        $validated = $request->validated();
        $hall->update($validated);
        $hallSeatGenerator->appendToCapacity($hall->fresh());
        $hall->loadCount($this->hallCountRelations());

        $seatMapService->broadcastForBranch($this->activeBranchId($request));

        $payload = [
            'message' => "Hall \"{$hall->name}\" updated.",
            'hall' => $this->serializeHall($hall),
        ];

        return $request->wantsJson()
            ? response()->json($payload)
            : redirect()->route('halls.index')->with('status', $payload['message']);
    }

    public function destroy(Request $request, Hall $hall, SeatMapService $seatMapService): JsonResponse|RedirectResponse
    {
        $this->authorizeHall($request, $hall);

        if ($hall->hasAssignedStudents()) {
            $payload = ['message' => 'Cannot delete a hall that has assigned students.'];

            return $request->wantsJson()
                ? response()->json($payload, 422)
                : redirect()->route('halls.index')->with('error', $payload['message']);
        }

        $name = $hall->name;
        $hall->delete();

        $seatMapService->broadcastForBranch($this->activeBranchId($request));

        $payload = ['message' => "Hall \"{$name}\" deleted."];

        return $request->wantsJson()
            ? response()->json($payload)
            : redirect()->route('halls.index')->with('status', $payload['message']);
    }

    public function bulkDestroy(Request $request, SeatMapService $seatMapService): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $halls = Hall::query()
            ->where('branch_id', $this->activeBranchId($request))
            ->whereIn('id', $validated['ids'])
            ->get();

        $blocked = $halls->filter(fn (Hall $hall) => $hall->hasAssignedStudents());

        if ($blocked->isNotEmpty()) {
            return response()->json([
                'message' => 'Cannot delete halls that have assigned students.',
            ], 422);
        }

        $deleted = Hall::query()
            ->where('branch_id', $this->activeBranchId($request))
            ->whereIn('id', $validated['ids'])
            ->delete();

        $seatMapService->broadcastForBranch($this->activeBranchId($request));

        return response()->json([
            'message' => "{$deleted} hall(s) deleted.",
            'deleted' => $deleted,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $halls = Hall::query()
            ->where('branch_id', $this->activeBranchId($request))
            ->withCount($this->hallCountRelations())
            ->orderBy('name')
            ->get();

        $filename = 'halls-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($halls) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Hall', 'Capacity', 'Filled', 'Description', 'Created At']);

            foreach ($halls as $hall) {
                fputcsv($handle, [
                    $hall->name,
                    $hall->seat_capacity,
                    $hall->filled_seats_count,
                    $hall->description,
                    $hall->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function authorizeHall(Request $request, Hall $hall): void
    {
        if ($request->user()?->isPlatformAdmin()) {
            abort_unless(Branch::query()->whereKey($hall->branch_id)->exists(), 403);

            return;
        }

        abort_unless($hall->branch_id === $this->activeBranchId($request), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHall(Hall $hall): array
    {
        return [
            'id' => $hall->id,
            'branch_id' => $hall->branch_id,
            'branch_name' => $hall->branch?->name ?? Branch::find($hall->branch_id)?->name,
            'name' => $hall->name,
            'description' => $hall->description,
            'seat_capacity' => $hall->seat_capacity,
            'filled_seats_count' => $hall->filled_seats_count ?? 0,
            'has_assigned_students' => $hall->hasAssignedStudents(),
            'min_seat_capacity' => $hall->minimumSeatCapacity(),
            'created_at' => $hall->created_at?->format('M d, Y'),
        ];
    }

    /**
     * @return array<string, \Closure|string>
     */
    private function hallCountRelations(): array
    {
        return [
            'seats',
            'seats as filled_seats_count' => fn ($query) => $query->whereHas(
                'bookings',
                fn ($bookingQuery) => $bookingQuery
                    ->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled'),
            ),
        ];
    }
}
