<?php

namespace App\Http\Controllers;

use App\Services\FeeService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(Request $request, FeeService $feeService): View
    {
        $rows = $feeService->baseQuery($this->activeBranchId($request))
            ->orderByDesc('id')
            ->get()
            ->map(fn ($booking) => $feeService->serializeRow($booking));

        return view('fees.index', compact('rows'));
    }
}
