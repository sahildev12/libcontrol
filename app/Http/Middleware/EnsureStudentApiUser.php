<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentApiUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Student) {
            abort(403, 'Student authentication required.');
        }

        if ($user->status !== 'active') {
            $user->currentAccessToken()?->delete();

            abort(403, 'This student account is inactive.');
        }

        return $next($request);
    }
}
