<?php

namespace App\Http\Controllers;

use App\Support\LibcontrolMarketingSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class LibcontrolMarketingSiteController extends Controller
{
    public function asset(Request $request, string $assetPath): Response|BinaryFileResponse
    {
        return $this->file($request, 'assets/'.$assetPath);
    }

    public function file(Request $request, string $path = 'index.html'): Response|BinaryFileResponse
    {
        if (! LibcontrolMarketingSite::shouldServe($request)) {
            abort(404);
        }

        $resolved = LibcontrolMarketingSite::resolveFile($path);
        if ($resolved === null) {
            abort(404);
        }

        return $this->fileResponse($resolved);
    }

    public function sendDemo(Request $request): Response
    {
        if (! LibcontrolMarketingSite::shouldServe($request)) {
            abort(404);
        }

        $script = LibcontrolMarketingSite::resolveFile('send-demo.php');
        if ($script === null) {
            abort(404);
        }

        ob_start();
        try {
            require $script;
        } finally {
            $output = ob_get_clean();
        }

        $status = http_response_code();
        if ($status === false || $status === 200) {
            $status = Response::HTTP_OK;
        }

        return response($output ?? '', $status, [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function fileResponse(string $absolutePath): BinaryFileResponse
    {
        $response = response()->file($absolutePath, [
            'Content-Type' => LibcontrolMarketingSite::mimeType($absolutePath),
        ]);

        if (! str_ends_with(strtolower($absolutePath), '.html')) {
            $response->setPublic();
            $response->setMaxAge(86400);
            $response->setLastModified(File::lastModified($absolutePath));
        }

        return $response;
    }
}
