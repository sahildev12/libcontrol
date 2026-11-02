<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\Tenancy\TrialProvisioner;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrialSignupController extends Controller implements HasMiddleware
{
    private const STEP_LABELS = [
        'subdomain' => 'Reserving your library address',
        'database' => 'Creating your private database',
        'migrate' => 'Installing LibControl',
        'admin' => 'Creating your admin account',
        'ssl' => 'Securing your site with SSL',
    ];

    public function __construct(private TrialProvisioner $trials) {}

    public static function middleware(): array
    {
        return [
            function (Request $request, Closure $next) {
                abort_unless(config('libcontrol.trials.enabled') && config('libcontrol.tenancy.enabled'), 404);

                return $next($request);
            },
        ];
    }

    public function create(): View
    {
        return view('trials.signup', [
            'baseDomain' => (string) config('libcontrol.tenancy.base_domain'),
            'trialDays' => (int) config('libcontrol.trials.days', 14),
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        try {
            $subdomain = $this->trials->normalizeAndValidateSubdomain((string) $request->query('subdomain', ''));
        } catch (ValidationException $e) {
            return response()->json(['available' => false, 'message' => $e->errors()['subdomain'][0] ?? 'Not available.']);
        }

        return response()->json([
            'available' => true,
            'subdomain' => $subdomain,
            'message' => $subdomain.'.'.config('libcontrol.tenancy.base_domain').' is available.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'library_name' => ['required', 'string', 'max:120'],
            'subdomain' => ['required', 'string', 'max:40'],
            'owner_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        if (filled($request->input('website'))) {
            throw ValidationException::withMessages(['email' => 'We could not start your trial. Please try again.']);
        }

        $limiterKey = 'trial-signup:'.$request->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('libcontrol.trials.signups_per_ip_per_day', 3))) {
            throw ValidationException::withMessages([
                'email' => 'Too many trials were started from this network today. Please contact support@libcontrol.in.',
            ]);
        }

        $tenant = $this->trials->register([
            'library_name' => $validated['library_name'],
            'subdomain' => $validated['subdomain'],
            'owner_name' => $validated['owner_name'],
            'owner_email' => $validated['email'],
            'password' => $validated['password'],
            'source' => 'email',
            'ip' => $request->ip(),
        ]);

        RateLimiter::hit($limiterKey, 86400);
        $request->session()->push('trial_signups', $tenant->signup_token);

        return redirect()->route('trials.building', $tenant->signup_token);
    }

    public function building(string $token): View
    {
        $tenant = $this->tenantForToken($token);

        return view('trials.building', [
            'tenant' => $tenant,
            'token' => $token,
            'steps' => self::STEP_LABELS,
        ]);
    }

    public function start(string $token): JsonResponse
    {
        $tenant = $this->tenantForToken($token);

        if (! $tenant->isReady()) {
            $this->trials->provision($tenant);
        }

        return response()->json(['started' => true]);
    }

    public function status(Request $request, string $token): JsonResponse
    {
        $tenant = $this->tenantForToken($token);
        $current = array_search($tenant->provision_step, array_keys(self::STEP_LABELS), true);

        $steps = [];
        foreach (array_keys(self::STEP_LABELS) as $index => $key) {
            $steps[$key] = match (true) {
                $tenant->isReady() => 'done',
                $current === false => $index === 0 ? 'active' : 'pending',
                $index < $current => 'done',
                $index === $current => $tenant->status === Tenant::STATUS_FAILED ? 'failed' : 'active',
                default => 'pending',
            };
        }

        $payload = [
            'status' => $tenant->status,
            'steps' => $steps,
            'host' => $tenant->host(),
        ];

        if ($tenant->status === Tenant::STATUS_FAILED) {
            $payload['message'] = 'Something went wrong while building your library. You can try again, or email support@libcontrol.in.';
        }

        if ($tenant->isReady()) {
            $payload['redirect'] = in_array($token, (array) $request->session()->get('trial_signups', []), true)
                ? $this->tenantUrl($request, $tenant, '/trial/enter/'.$tenant->issueLoginToken())
                : $this->tenantUrl($request, $tenant, '/admin/login');
        }

        return response()->json($payload);
    }

    private function tenantUrl(Request $request, Tenant $tenant, string $path): string
    {
        $port = in_array($request->getPort(), [80, 443], true) ? '' : ':'.$request->getPort();

        return $request->getScheme().'://'.$tenant->host().$port.$path;
    }

    private function tenantForToken(string $token): Tenant
    {
        return Tenant::query()->where('signup_token', $token)->where('is_trial', true)->firstOrFail();
    }
}
