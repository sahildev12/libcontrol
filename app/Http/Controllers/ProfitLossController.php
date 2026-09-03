<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Services\ProfitLossService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ProfitLossController extends Controller
{
    public function index(Request $request, ProfitLossService $profitLossService): View
    {
        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $from = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'), $tz)->startOfDay()
            : Carbon::now($tz)->startOfMonth();
        $to = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'), $tz)->endOfDay()
            : Carbon::now($tz)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $branchId = $this->optionalActiveBranchId($request);
        $summary = $profitLossService->summary($branchId, $from, $to);

        $expenses = $this->constrainByActiveBranch(
            Expense::query()->with(['branch:id,name', 'recorder:id,name']),
            $request,
        )
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Expense $expense) => $profitLossService->serializeExpense($expense));

        $branches = $request->user()?->isPlatformAdmin()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('profit-loss.index', [
            'expenses' => $expenses,
            'summary' => $summary,
            'categories' => config('libcontrol.expense_categories', []),
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'scopeLabel' => $this->viewingAllBranches($request)
                ? 'all branches'
                : ($this->optionalActiveBranch($request)?->name ?? ''),
            'branches' => $branches,
            'viewingAll' => $this->viewingAllBranches($request),
            'defaultBranchId' => $this->optionalActiveBranchId($request),
        ]);
    }

    public function store(StoreExpenseRequest $request, ProfitLossService $profitLossService): JsonResponse
    {
        $branch = $this->resolveWritableBranch($request, $request->integer('branch_id') ?: null);
        abort_unless($branch, 403);

        $expense = Expense::query()->create([
            'branch_id' => $branch->id,
            'category' => $request->string('category'),
            'title' => $request->string('title'),
            'amount' => round((float) $request->input('amount'), 2),
            'expense_date' => $request->date('expense_date'),
            'payment_method' => $request->string('payment_method'),
            'notes' => $request->input('notes'),
            'recorded_by' => $request->user()?->id,
        ]);

        $this->logActivity(
            $request,
            'expenses.created',
            "Recorded expense {$expense->title} (₹{$expense->amount}).",
            $expense,
            $branch->id,
        );

        return response()->json([
            'message' => 'Expense recorded.',
            'row' => $profitLossService->serializeExpense($expense->fresh(['branch:id,name', 'recorder:id,name'])),
        ], 201);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, ProfitLossService $profitLossService): JsonResponse
    {
        $this->assertCanAccessBranch($request, $expense->branch_id);

        $expense->update([
            'category' => $request->string('category'),
            'title' => $request->string('title'),
            'amount' => round((float) $request->input('amount'), 2),
            'expense_date' => $request->date('expense_date'),
            'payment_method' => $request->string('payment_method'),
            'notes' => $request->input('notes'),
        ]);

        $this->logActivity(
            $request,
            'expenses.updated',
            "Updated expense {$expense->title}.",
            $expense,
            $expense->branch_id,
        );

        return response()->json([
            'message' => 'Expense updated.',
            'row' => $profitLossService->serializeExpense($expense->fresh(['branch:id,name', 'recorder:id,name'])),
        ]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->assertCanAccessBranch($request, $expense->branch_id);

        $title = $expense->title;
        $branchId = $expense->branch_id;
        $expense->delete();

        $this->logActivity($request, 'expenses.deleted', "Deleted expense {$title}.", null, $branchId);

        return response()->json(['message' => 'Expense deleted.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $expenses = $this->constrainByActiveBranch(Expense::query(), $request)
            ->whereIn('id', $validated['ids'])
            ->get();

        foreach ($expenses as $expense) {
            $expense->delete();
        }

        $this->logActivity($request, 'expenses.bulk_deleted', 'Removed '.$expenses->count().' expense record(s).');

        return response()->json([
            'message' => $expenses->count().' expense record(s) removed.',
            'deleted' => $expenses->count(),
        ]);
    }
}
