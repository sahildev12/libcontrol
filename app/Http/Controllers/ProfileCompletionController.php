<?php

namespace App\Http\Controllers;

use App\Services\Profile\LibraryProfileCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileCompletionController extends Controller
{
    public function index(Request $request, LibraryProfileCompletionService $completion): View
    {
        abort_if($request->user()?->isDeveloperAdmin(), 404);

        $score = $completion->scoreForLibrary($request->user());

        return view('profile-completion.index', [
            'completion' => $score,
        ]);
    }

    public function continue(Request $request): RedirectResponse
    {
        abort_if($request->user()?->isDeveloperAdmin(), 404);

        $request->session()->put('profile_completion_seen', true);

        return redirect()->route('dashboard');
    }
}
