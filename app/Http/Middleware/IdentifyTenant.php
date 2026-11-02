<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantConnectionManager;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class IdentifyTenant
{
    public function __construct(
        private TenantConnectionManager $connections,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('libcontrol.tenancy.enabled')) {
            return $next($request);
        }

        if (! File::exists(storage_path('app/install.lock'))) {
            TenantContext::setLandlordMode();
            $this->connections->useLandlord();

            return $next($request);
        }

        try {
            $host = strtolower($request->getHost());
            $landlordHosts = collect(config('libcontrol.tenancy.landlord_hosts', []))
                ->map(fn (string $value) => strtolower($value))
                ->all();

            if (in_array($host, $landlordHosts, true)) {
                TenantContext::setLandlordMode();
                $this->connections->useLandlord();

                return $next($request);
            }

            $baseDomain = strtolower((string) config('libcontrol.tenancy.base_domain', ''));
            $suffix = '.'.$baseDomain;

            if ($baseDomain !== '' && str_ends_with($host, $suffix)) {
                $subdomain = Tenant::normalizeSubdomain(substr($host, 0, -strlen($suffix)));

                if ($subdomain === '') {
                    abort(404, 'Library not found.');
                }

                $tenant = Tenant::query()
                    ->where('subdomain', $subdomain)
                    ->where('active', true)
                    ->first();

                if (! $tenant) {
                    abort(404, 'This library is not available yet.');
                }

                TenantContext::activate($tenant);
                $this->connections->useTenant($tenant);
                $this->isolateCache($tenant);

                return $next($request);
            }

            TenantContext::setLandlordMode();
            $this->connections->useLandlord();
        } catch (\Throwable $e) {
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }

            TenantContext::setLandlordMode();
            $this->connections->useLandlord();
        }

        return $next($request);
    }

    /**
     * All tenants share one codebase and storage folder, so cached values must not leak between libraries.
     */
    private function isolateCache(Tenant $tenant): void
    {
        $key = 'tenant_'.$tenant->id;

        Config::set('cache.prefix', $key.'_'.config('cache.prefix'));
        Config::set('cache.stores.file.path', storage_path('framework/cache/data/'.$key));

        Cache::forgetDriver();
        Cache::forgetDriver('file');
    }
}
