<?php

namespace App\Http\Controllers;

use App\Support\LibcontrolMarketingSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicHomeController extends Controller
{
    public function __invoke(Request $request, LibraryWebsiteController $libraryWebsite, LibcontrolMarketingSiteController $marketingSite): RedirectResponse|\Illuminate\View\View|\Symfony\Component\HttpFoundation\Response
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        if (LibcontrolMarketingSite::shouldServe($request)) {
            return $marketingSite->file($request, 'index.html');
        }

        return $libraryWebsite->home($request, app(\App\Services\LibraryWebsiteService::class));
    }
}
