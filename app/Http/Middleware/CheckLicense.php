<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use function array_unique;
use function array_values;
use function explode;
use function trim;

class CheckLicense
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    public function handle(Request $request, Closure $next, string ...$requiredModules): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        if (! $request->user()) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $user = $request->user();
        $license = $this->licenseService->resolveApplicableLicense($user);

        // Geçerli (aktif + süresi dolmamış) lisans bulunamadı → kapı kapalı
        if ($license === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Lisansınızın süresi dolmuştur veya geçerli lisans bulunamadı.'], 402);
            }

            return response()->view('errors.license-blocked', [], 402);
        }

        if (! $this->licenseService->isUserCountWithinLimit($license)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Lisans kullanıcı limiti aşılmıştır.'], 402);
            }

            return response()->view('errors.license-blocked', [], 402);
        }

        $normalizedModules = $this->normalizeModules($requiredModules);
        if (! $this->licenseService->licenseAllowsModules($license, $normalizedModules)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bu modül için lisans izni bulunmuyor.'], 403);
            }

            return response()->view('errors.license-blocked', [], 403);
        }

        return $next($request);
    }

    protected function shouldBypass(Request $request): bool
    {
        // Auth / lisans engel sayfaları
        if ($request->routeIs([
            'license.blocked',
            'login',
            'register',
            'logout',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
            'password.update',
            'verification.notice',
            'verification.verify',
            'verification.send',
        ])) {
            return true;
        }

        // Super-admin lisans yönetir, kendi lisansından bağımsız her zaman erişir
        if ($request->user()?->hasRole('super-admin')) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $requiredModules
     * @return array<int, string>
     */
    protected function normalizeModules(array $requiredModules): array
    {
        $normalized = [];

        foreach ($requiredModules as $moduleString) {
            foreach (explode(',', $moduleString) as $candidate) {
                $module = trim($candidate);
                if ($module !== '') {
                    $normalized[] = $module;
                }
            }
        }

        return array_values(array_unique($normalized));
    }
}
