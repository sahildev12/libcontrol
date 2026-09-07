<?php

namespace App\Http\Controllers;

use App\Services\Addons\AddonRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function install(Request $request, string $slug, AddonRegistry $addons): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $addons->install($slug);

        $this->logActivity($request, 'addons.installed', "Installed addon {$slug}.");

        return response()->json([
            'message' => 'Addon installed.',
            'addon' => $addons->serializeForSettings($slug),
        ]);
    }

    public function enable(Request $request, string $slug, AddonRegistry $addons): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);
        abort_unless($addons->isInstalled($slug), 404);

        $addons->enable($slug);

        $this->logActivity($request, 'addons.enabled', "Enabled addon {$slug}.");

        return response()->json([
            'message' => 'Addon enabled.',
            'addon' => $addons->serializeForSettings($slug),
        ]);
    }

    public function disable(Request $request, string $slug, AddonRegistry $addons): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);
        abort_unless($addons->isInstalled($slug), 404);

        $addons->disable($slug);

        $this->logActivity($request, 'addons.disabled', "Disabled addon {$slug}.");

        return response()->json([
            'message' => 'Addon disabled.',
            'addon' => $addons->serializeForSettings($slug),
        ]);
    }

    public function destroy(Request $request, string $slug, AddonRegistry $addons): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);
        abort_unless($addons->isInstalled($slug), 404);

        $addons->disable($slug);
        $addons->uninstall($slug);

        $this->logActivity($request, 'addons.uninstalled', "Uninstalled addon {$slug}.");

        return response()->json([
            'message' => 'Addon uninstalled.',
            'addon' => $addons->serializeForSettings($slug),
        ]);
    }
}
